<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\site_settings\Entity\SiteSettingEntity;

/**
 * The default service to load the site settings.
 *
 * @package Drupal\site_settings
 */
class SiteSettingsLoader implements SiteSettingsLoaderInterface {

  /**
   * Cache BIN for settings.
   *
   * @var string
   */
  protected const SITE_SETTINGS_CACHE_BIN = 'site_settings';

  /**
   * Cache CID for settings.
   *
   * @var string
   */
  protected const SITE_SETTINGS_CACHE_CID = 'settings';

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Variable to store the loaded settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function loadByFieldset($fieldset, $langcode = NULL) {
    $this->loadAll(FALSE, $langcode);
    $fieldset = $this->fieldsetKey($fieldset);
    return $this->settings[$fieldset] ?? [];
  }

  /**
   * {@inheritDoc}
   */
  public function loadAll($rebuild_cache = FALSE, $langcode = NULL) {
    $langcode = $langcode ?? $this->languageManager->getCurrentLanguage()->getId();
    if (!$rebuild_cache && $cache = \Drupal::cache(self::SITE_SETTINGS_CACHE_BIN)->get(self::SITE_SETTINGS_CACHE_CID . ':' . $langcode)) {
      $this->settings = $cache->data;
    }
    else {
      $this->rebuildCache($langcode);
    }
    return $this->settings;
  }

  /**
   * {@inheritDoc}
   */
  public function rebuildCache($langcode) {
    $this->buildSettings($langcode);
    \Drupal::cache(self::SITE_SETTINGS_CACHE_BIN)->set(self::SITE_SETTINGS_CACHE_CID . ':' . $langcode, $this->settings);
  }

  /**
   * {@inheritDoc}
   */
  public function clearCache() {
    \Drupal::cache(self::SITE_SETTINGS_CACHE_BIN)->deleteAll();
  }

  /**
   * Build the settings array.
   */
  private function buildSettings($langcode) {

    // Clear the existing settings to avoid empty keys.
    $this->settings = [];

    // Get all site settings.
    $setting_entities = SiteSettingEntity::loadMultiple();

    // Get entity type configurations at once for performance.
    $entities = [];
    $entity_type = $this->entityTypeManager->getStorage('site_setting_entity_type');
    if ($entity_type) {
      $entities = $entity_type->loadMultiple();
    }

    foreach ($setting_entities as $entity) {
      if (method_exists($entity, 'hasTranslation') && $entity->hasTranslation($langcode)) {
        $entity = $entity->getTranslation($langcode);
      }

      // Get data.
      $fieldset = $entity->fieldset->getValue()[0]['value'];
      $fieldset = $this->fieldsetKey($fieldset);
      $type = $entity->type->getValue()[0]['target_id'];
      $multiple = (isset($entities[$type]) ? $entities[$type]->multiple : FALSE);

      // If we have multiple, set as array of entities.
      if ($multiple) {
        if (!isset($this->settings[$fieldset][$type])) {
          $this->settings[$fieldset][$type] = [];
        }
        $this->settings[$fieldset][$type][] = $this->getValues($entity);
      }
      else {
        $this->settings[$fieldset][$type] = $this->getValues($entity);
      }
    }

    // Get all possibilities and fill with empty values.
    $bundles = $this->entityTypeManager
      ->getStorage('site_setting_entity_type')
      ->loadMultiple();
    foreach ($bundles as $bundle) {
      $fieldset = $this->fieldsetKey($bundle->fieldset);
      $id = $bundle->id();

      // Only fill if not yet set.
      if (!isset($this->settings[$fieldset][$id])) {
        $this->settings[$fieldset][$id] = '';
      }
    }
  }

  /**
   * Get the values from the entity and return in as simple an array possible.
   *
   * @param object $entity
   *   Field Entity.
   *
   * @return mixed
   *   The values.
   */
  private function getValues($entity) {
    $build = [];
    $fields = $entity->getFields();
    foreach ($fields as $key => $field) {
      /** @var \Drupal\Core\Field\FieldItemInterface $field */
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
      $field_definition = $field->getFieldDefinition();

      // Exclude fields on the object that are base config.
      if (!method_exists(get_class($field_definition), 'isBaseField') || !$field_definition->isBaseField()) {

        if (($value = $this->getValue($field)) || $field_definition->getType() == 'boolean') {
          $build[$key] = $value;

          // Add supplementary data to some field types.
          switch ($field_definition->getType()) {
            case 'link':
              $build[$key] = $this->addSupplementaryLinkData($build[$key], $field);
              break;

            case 'image':
            case 'file':
            case 'svg_image_field':
              $build[$key] = $this->addSupplementaryImageData($build[$key], $field);
              break;
          }
        }
      }
    }

    // Flatten array as much as possible.
    if (count($build) == 1) {

      // Pass back single value.
      return reset($build);
    }
    elseif (count($build) == 2 && isset($build['user_id'])) {

      // If site setting is translated, remove meta user_id field.
      unset($build['user_id']);
      return reset($build);
    }
    else {

      // Unable to flatten further, return for array.
      return $build;
    }
  }

  /**
   * Get the value for the particular field item.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field object.
   *
   * @return bool|array
   *   The value or false.
   */
  private function getValue(FieldItemListInterface $field) {
    if ($value = $field->getValue()) {

      // Store the values in as flat a way as possible based on what is set.
      if (count($value) <= 1) {
        $item = reset($value);
        if (count($item) <= 1) {
          return reset($item);
        }
        else {
          return $item;
        }
      }
      else {
        return $value;
      }
    }
    return FALSE;
  }

  /**
   * Add supplementary link data to the site settings.
   *
   * @param array $data
   *   The existing data.
   * @param object $field
   *   The field object.
   *
   * @return array
   *   The data with the new supplementary information included.
   */
  private function addSupplementaryLinkData(array $data, $field) {
    if (isset($field->uri) && $url = Url::fromUri($field->uri)) {
      $data = array_merge($data, [
        'url' => $url,
      ]);
    }
    return $data;
  }

  /**
   * Add supplementary image data to the site settings.
   *
   * @param array $data
   *   The existing data.
   * @param object $field
   *   The field object.
   *
   * @return array
   *   The data with the new supplementary information included.
   */
  private function addSupplementaryImageData(array $data, $field) {
    if ($entities = $field->referencedEntities()) {
      if (count($entities) > 1) {

        // If multiple images add data to each.
        foreach ($data as $key => $sub_image_data) {
          /** @var \Drupal\file\FileInterface $entity */
          $entity = $entities[$key];
          $data[$key]['filename'] = $entity->getFilename();
          $data[$key]['uri'] = $entity->getFileUri();
          $data[$key]['mime_type'] = $entity->getMimeType();
          $data[$key]['size'] = $entity->getSize();
          $data[$key]['is_permanent'] = $entity->isPermanent();
        }
      }
      else {

        // Add the entity to the image.
        /** @var \Drupal\file\FileInterface $entity */
        $entity = reset($entities);
        $data['filename'] = $entity->getFilename();
        $data['uri'] = $entity->getFileUri();
        $data['mime_type'] = $entity->getMimeType();
        $data['size'] = $entity->getSize();
        $data['is_permanent'] = $entity->isPermanent();
      }
    }
    return $data;
  }

  /**
   * Create a lowercase key with no spaces from the fieldset label.
   *
   * @param string $fieldset
   *   The fieldset key.
   *
   * @return string
   *   Updated fieldset key.
   */
  private function fieldsetKey($fieldset) {
    return strtolower(str_replace(' ', '_', $fieldset));
  }

}
