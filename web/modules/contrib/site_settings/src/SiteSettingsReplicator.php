<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * The site settings replicatior service.
 *
 * @package Drupal\site_settings
 */
class SiteSettingsReplicator {
  use StringTranslationTrait;

  /**
   * Drupal\replicate\Replicator definition.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicateReplicator;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\field_tools\FieldCloner definition.
   *
   * @var \Drupal\field_tools\FieldCloner
   */
  protected $fieldToolsFieldCloner;

  /**
   * Drupal\field_tools\DisplayCloner definition.
   *
   * @var \Drupal\field_tools\DisplayCloner
   */
  protected $fieldToolsDisplayCloner;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Process callback for the batch set the export form.
   *
   * @param array $settings
   *   The settings from the export form.
   * @param array $context
   *   The batch context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processBatch(array $settings, array &$context) {
    if (empty($context['sandbox'])) {

      // Clean settings.
      $settings = $this->cleanSettings($settings);

      // Store data in results for batch finish.
      $context['results']['settings'] = $settings;

      // Set initial batch progress.
      $context['sandbox']['settings'] = $settings;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = 0;
      $context['sandbox']['max'] = count($settings['values']['new_settings']);

    }
    else {
      $settings = $context['sandbox']['settings'];
    }

    if ($context['sandbox']['max'] == 0) {

      // If we have no settings to process, immediately finish.
      $context['finished'] = 1;

    }
    else {

      // Load the optional services.
      $this->replicateReplicator = \Drupal::service('replicate.replicator');
      $this->fieldToolsFieldCloner = \Drupal::service('field_tools.field_cloner');
      $this->fieldToolsDisplayCloner = \Drupal::service('field_tools.display_cloner');

      // Replicate the next setting.
      $key = $context['sandbox']['progress'];
      $setting = $settings['values']['new_settings'][$key];
      $this->replicateSetting($setting, $settings['values']['setting']);

      $context['results']['current_id'] = $key;
      $context['sandbox']['progress']++;
      $context['sandbox']['current_id'] = $key;

      // Set the current message.
      $context['message'] = $this->t('Processed @num of @total new settings.', [
        '@num' => $context['sandbox']['progress'],
        '@total' => $context['sandbox']['max'],
      ]);

      // Check if we are now finished.
      if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      }

    }

  }

  /**
   * Clean the settings.
   *
   * Remove any settings that are completely empty as there is nothing to
   * process for those.
   *
   * @param array $settings
   *   The settings from the replicate form.
   *
   * @return int
   *   The cleaned settings.
   */
  protected function cleanSettings(array $settings) {
    $new_settings = [];
    foreach ($settings['values']['new_settings'] as $key => $setting) {
      if (!empty($setting['machine_name']) && !empty($setting['label']) && !empty($setting['fieldset'])) {
        $new_settings[] = $setting;
      }
    }
    $settings['values']['new_settings'] = $new_settings;
    return $settings;
  }

  /**
   * Run the replication process for a single new setting.
   *
   * @param array $setting
   *   A single new setting.
   * @param string $original_setting_name
   *   The setting name to replicate from.
   *
   * @return bool
   *   Successful completion.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function replicateSetting(array $setting, $original_setting_name) {

    // Replicate site_settings_entity_type.
    $site_setting_entity_type = $this->entityTypeManager
      ->getStorage('site_setting_entity_type')
      ->load($original_setting_name);
    $site_settings_entity_type_duplicate = $site_setting_entity_type->createDuplicate();
    $site_settings_entity_type_duplicate->set('id', $setting['machine_name']);
    $site_settings_entity_type_duplicate->save();

    // Replicate site_settings_entity.
    $site_settings_entities = $this->entityTypeManager
      ->getStorage('site_setting_entity')
      ->loadByProperties([
        'type' => $original_setting_name,
      ]);
    $site_settings_entity = reset($site_settings_entities);
    $site_settings_entity_duplicate = $this->replicateReplicator
      ->replicateByEntityId('site_setting_entity', $site_settings_entity->id());
    $site_settings_entity_duplicate->setType($site_settings_entity_type_duplicate->id());
    $site_settings_entity_duplicate->setName($setting['label']);
    $site_settings_entity_duplicate->setFieldset($setting['fieldset']);
    $site_settings_entity_duplicate->save();

    // Replicate site_settings_entity fields.
    if ($field_definitions = $site_settings_entity->getFieldDefinitions()) {
      foreach ($field_definitions as $field_definition) {

        // Base fields are copied already, we only want fields from the
        // manage fields tab.
        if (array_key_exists(
          'Drupal\field\FieldConfigInterface',
          class_implements($field_definition)
        )) {
          $bundle = $site_settings_entity_duplicate->id();
          $this->fieldToolsFieldCloner
            ->cloneField(
              $field_definition,
              'site_setting_entity',
              $site_settings_entity_type_duplicate->id()
            );
        }
      }
    }

    // Replicate form display.
    $form_display = $this->entityTypeManager
      ->getStorage('entity_form_display')
      ->load('site_setting_entity.' . $original_setting_name . '.default');
    $this->fieldToolsDisplayCloner
      ->cloneDisplay($form_display, $setting['machine_name']);

    // Replicate view display.
    $view_display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load('site_setting_entity.' . $original_setting_name . '.default');
    $this->fieldToolsDisplayCloner
      ->cloneDisplay($view_display, $setting['machine_name']);

    return TRUE;
  }

  /**
   * Finish callback for the batch replicate form.
   *
   * @param bool $success
   *   Whether the batch was successful or not.
   * @param array $results
   *   The bath results.
   * @param array $operations
   *   The batch operations.
   */
  public function finishBatch($success, array $results, array $operations) {
    if (!$success) {
      $message = $this->t('The settings creation was unsuccessful for an unknown reason. Please check your error logs.');
      $messenger = \Drupal::messenger();
      $messenger->addWarning($message);
    }

    // Redirect back to manage site settings page.
    $url = Url::fromRoute('entity.site_setting_entity_type.collection');
    $url_string = $url->toString();
    $response = new RedirectResponse($url_string);
    $response->send();

  }

}
