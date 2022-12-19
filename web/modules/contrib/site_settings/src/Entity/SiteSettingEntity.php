<?php

namespace Drupal\site_settings\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\site_settings\SiteSettingEntityInterface;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;

/**
 * Defines the Site Setting entity.
 *
 * @ingroup site_settings
 *
 * @ContentEntityType(
 *   id = "site_setting_entity",
 *   label = @Translation("Site Setting"),
 *   bundle_label = @Translation("Site Setting type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\site_settings\SiteSettingEntityListBuilder",
 *     "views_data" = "Drupal\site_settings\Entity\SiteSettingEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\site_settings\Form\SiteSettingEntityForm",
 *       "add" = "Drupal\site_settings\Form\SiteSettingEntityForm",
 *       "edit" = "Drupal\site_settings\Form\SiteSettingEntityForm",
 *       "delete" = "Drupal\site_settings\Form\SiteSettingEntityDeleteForm",
 *     },
 *     "access" = "Drupal\site_settings\SiteSettingEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\site_settings\SiteSettingEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "site_setting_entity",
 *   data_table = "site_setting_entity_field_data",
 *   admin_permission = "administer site setting entities",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "fieldset" = "fieldset",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/site_setting_entity/{site_setting_entity}",
 *     "add-form" = "/admin/structure/site_setting_entity/add/{site_setting_entity_type}",
 *     "edit-form" = "/admin/structure/site_setting_entity/{site_setting_entity}/edit",
 *     "delete-form" = "/admin/structure/site_setting_entity/{site_setting_entity}/delete",
 *     "collection" = "/admin/structure/site_setting_entity",
 *   },
 *   bundle_entity_type = "site_setting_entity_type",
 *   field_ui_base_route = "entity.site_setting_entity_type.edit_form"
 * )
 */
class SiteSettingEntity extends ContentEntityBase implements SiteSettingEntityInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldset() {
    return $this->get('fieldset')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFieldset($fieldset) {
    $this->set('fieldset', $fieldset);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NodeInterface::PUBLISHED : NodeInterface::NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Site Setting entity.'))
      ->setReadOnly(TRUE);
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Site Setting type/bundle.'))
      ->setSetting('target_type', 'site_setting_entity_type')
      ->setRequired(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Site Setting entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Site Setting entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setTranslatable(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultUserId')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Site Setting entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fieldset'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Fieldset'))
      ->setDescription(t('The fieldset of the Site Setting entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValueCallback(static::class . '::getDefaultFieldset')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Site Setting is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Site Setting entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Default value callback for 'user_id' base field definition.
   *
   * @return array
   *   An array of default values.
   */
  public static function getDefaultUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * Default value callback for 'fieldset' base field definition.
   *
   * @param \Drupal\site_settings\Entity\SiteSettingEntity $entity
   *   The site setting entity.
   *
   * @return array
   *   An array of default values.
   */
  public static function getDefaultFieldset(SiteSettingEntity $entity) {
    $site_settings_entity_type = SiteSettingEntityType::load($entity->getType());
    return [$site_settings_entity_type->get('fieldset')];
  }

}
