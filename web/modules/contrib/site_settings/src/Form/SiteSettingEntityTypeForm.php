<?php

namespace Drupal\site_settings\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_settings\SiteSettingsLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The form to create new site setting types.
 *
 * @package Drupal\site_settings\Form
 */
class SiteSettingEntityTypeForm extends EntityForm {

  /**
   * The site settings loader service.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderInterface
   */
  protected $siteSettingsLoader;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\site_settings\SiteSettingsLoaderInterface $site_settings_loader
   *   The site settings loader service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(SiteSettingsLoaderInterface $site_settings_loader, ModuleHandlerInterface $module_handler) {
    $this->siteSettingsLoader = $site_settings_loader;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('site_settings.loader'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\site_settings\Entity\SiteSettingEntityType $site_setting_entity_type */
    $site_setting_entity_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $site_setting_entity_type->label(),
      '#description' => $this->t("The label for the particular setting."),
      '#required' => TRUE,
    ];

    $fieldsets = $this->getFieldsets($site_setting_entity_type);
    if ($fieldsets) {
      array_unshift($fieldsets, $this->getCreateNewLabel());
      $form['existing_fieldset'] = [
        '#type' => 'select',
        '#title' => $this->t('Choose existing "Fieldset Legend" label'),
        '#options' => array_combine($fieldsets, $fieldsets),
        '#default_value' => $site_setting_entity_type->fieldset,
        '#description' => $this->t("The fieldset to group this particular setting in."),
        '#required' => TRUE,
        '#empty_option' => '-- select one --',
        '#empty_value' => '',
      ];
    }
    $form['new_fieldset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Create a new "Fieldset Legend" label'),
      '#maxlength' => 255,
      '#default_value' => $site_setting_entity_type->fieldset,
      '#description' => $this->t("A new fieldset to group this particular setting in."),
      '#required' => FALSE,
    ];
    if ($fieldsets) {
      $form['new_fieldset']['#states'] = [
        'visible' => [
          ':input[name="existing_fieldset"]' => ['value' => '-- create new fieldset --'],
        ],
        'required' => [
          ':input[name="existing_fieldset"]' => ['value' => '-- create new fieldset --'],
        ],
      ];
    }

    $form['fieldset'] = [
      '#type' => 'hidden',
      '#default_value' => $site_setting_entity_type->fieldset,
    ];

    $form['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multiple'),
      '#default_value' => $site_setting_entity_type->multiple,
      '#description' => $this->t("Whether or not to allow multiple entries for this same setting."),
    ];

    $form['instructions'] = [
      '#markup' => '<p>' . $this->t('Please be diligent to reuse existing fields via the "Manage Fields" tab when creating new Site Settings to avoid performance issues.') . '</p>',
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $site_setting_entity_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\site_settings\Entity\SiteSettingEntityType::load',
      ],
      '#disabled' => !$site_setting_entity_type->isNew(),
    ];

    return $form;
  }

  /**
   * Get the create new label. This is reused.
   *
   * @return string
   *   The label for the create new option.
   */
  private function getCreateNewLabel() {
    return $this->t('-- create new fieldset --');
  }

  /**
   * Get a list of fieldsets that already exist.
   *
   * @param object $entity_type
   *   The site settings entity type object.
   *
   * @return array
   *   The fieldsets.
   */
  private function getFieldsets($entity_type) {
    $fieldsets = [];
    if ($bundles = $entity_type->loadMultiple()) {
      foreach ($bundles as $bundle) {
        $fieldsets[] = $bundle->fieldset;
      }
    }
    return array_unique($fieldsets);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $values = $form_state->getValues();
    if (!isset($values['existing_fieldset']) || $values['existing_fieldset'] == $this->getCreateNewLabel()) {
      if (empty($values['new_fieldset'])) {
        $form_state->setErrorByName('new_fieldset', $this->t('Please enter a fieldset name.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!isset($values['existing_fieldset']) || $values['existing_fieldset'] == $this->getCreateNewLabel()) {
      $this->entity->fieldset = $values['new_fieldset'];
    }
    else {
      $this->entity->fieldset = $values['existing_fieldset'];
    }
    $this->entity->multiple = $values['multiple'];

    /** @var \Drupal\site_settings\Entity\SiteSettingEntityType $site_setting_entity_type */
    $site_setting_entity_type = $this->entity;
    $status = $site_setting_entity_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Site Setting type.', [
          '%label' => $site_setting_entity_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Site Setting type.', [
          '%label' => $site_setting_entity_type->label(),
        ]));
    }

    // Rebuild the site settings cache.
    $this->siteSettingsLoader->clearCache();

    $route_name = 'entity.site_setting_entity_type.collection';
    $route_parameters = [];
    if ($this->moduleHandler->moduleExists('field_ui')) {
      // Redirect the user to the add fields screen for this new entity type.
      $route_name = 'entity.site_setting_entity.field_ui_fields';
      $route_parameters = [
        'site_setting_entity_type' => $site_setting_entity_type->id(),
      ];
    }
    $form_state->setRedirect($route_name, $route_parameters);
  }

}
