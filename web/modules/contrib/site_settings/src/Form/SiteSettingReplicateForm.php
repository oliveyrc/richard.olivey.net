<?php

namespace Drupal\site_settings\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * A form to allow replication of an existing site setting.
 *
 * @package Drupal\site_settings\Form
 */
class SiteSettingReplicateForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_setting_replicate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $setting = FALSE) {

    // Optional dependencies check here as we don't want to force the use of
    // these modules for the core functionality of this module.
    $has_replicate = $this->moduleHandler->moduleExists('replicate');
    $has_field_tools = $this->moduleHandler->moduleExists('field_tools');
    if (!$has_replicate && !$has_field_tools) {
      $this->messenger()->addMessage($this->t('Please install the Replicate and Field Tools modules to use this feature.'), 'warning');
    }
    elseif (!$has_replicate) {
      $this->messenger()->addMessage($this->t('Please install the Replicate module to use this feature.'), 'warning');
    }
    elseif (!$has_field_tools) {
      $this->messenger()->addMessage($this->t('Please install the Field Tools module to use this feature.'), 'warning');
    }
    elseif ($site_setting_entity_type = $this->entityTypeManager->getStorage('site_setting_entity_type')->load($setting)) {
      /** @var \Drupal\site_settings\Entity\SiteSettingEntityType $site_setting_entity_type */

      // The form.
      $form['setting'] = [
        '#type' => 'hidden',
        '#value' => $setting,
      ];

      $form['description'] = [
        '#markup' => '<h2>' . $this->t('Replicating from setting: @setting', ['@setting' => $setting]) . '</h2>',
      ];

      // State that the form needs to allow for a hierarchy.
      $form['#tree'] = TRUE;

      // Initial number of names.
      if (!$form_state->get('num_to_generate')) {
        $form_state->set('num_to_generate', 1);
      }

      // Container for our repeating fields.
      $form['new_settings'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Machine name'),
          $this->t('Label'),
          $this->t('Fieldset'),
        ],
      ];

      // Add our names fields.
      for ($x = 0; $x < $form_state->get('num_to_generate'); $x++) {
        $form['new_settings'][$x]['machine_name'] = [
          '#type' => 'machine_name',
          '#title' => '',
          '#description' => '',
          '#size' => 30,
          '#required' => FALSE,
          '#machine_name' => [
            'exists' => [$this, 'machineNameExists'],
          ],
        ];

        $form['new_settings'][$x]['label'] = [
          '#type' => 'textfield',
          '#title' => '',
          '#description' => '',
          '#size' => 20,
        ];

        $form['new_settings'][$x]['fieldset'] = [
          '#type' => 'textfield',
          '#title' => '',
          '#description' => '',
          '#size' => 20,
          '#default_value' => $site_setting_entity_type->fieldset,
        ];
      }

      $form['button_container'] = [
        '#type' => 'container',
      ];

      $form['button_container']['add_setting'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add another setting'),
        '#limit_validation_errors' => [],
      ];

      $form['button_container']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Generate settings'),
      ];

    }
    else {
      $this->messenger()->addMessage($this->t('Unable to load setting.'), 'warning');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($existing = $this->entityTypeManager->getStorage('site_setting_entity_type')->loadMultiple()) {
      $values = $form_state->getValues();
      foreach ($values['new_settings'] as $key => $setting) {

        // Skip if all empty and not first row.
        if ($key > 0 && empty($setting['machine_name']) && empty($setting['label']) && empty($setting['fieldset'])) {
          continue;
        }

        // Double check that all 3 are filled in if any 1 is.
        if (empty($setting['machine_name'])) {
          $form_state->setErrorByName('new_settings][' . $key . '][machine_name', $this->t('Please enter a machine name'));
        }
        if (empty($setting['label'])) {
          $form_state->setErrorByName('new_settings][' . $key . '][label', $this->t('Please enter a label'));
        }
        if (empty($setting['fieldset'])) {
          $form_state->setErrorByName('new_settings][' . $key . '][fieldset', $this->t('Please enter a fieldset'));
        }

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function machineNameExists($value, array $form, FormStateInterface $form_state) {
    $matches = 0;

    // Check if exists.
    if ($existing = $this->entityTypeManager->getStorage('site_setting_entity_type')->loadMultiple()) {
      if (in_array($value, $existing)) {
        $matches++;
      }
    }

    // Check if the new ones we are adding match.
    $values = $form_state->getValues();
    foreach ($values['new_settings'] as $key => $setting) {
      if ($setting['machine_name'] == $value) {
        $matches++;
      }
    }

    // 1 match is allowed as that is self.
    if ($matches > 1) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    // Decide what action to take based on which button the user clicked.
    switch ($values['op']) {
      case 'Add another setting':
        $this->addNewFields($form, $form_state);
        break;

      default:
        $this->finalSubmit($form, $form_state);
    }

  }

  /**
   * Handle adding new.
   */
  private function addNewFields(array &$form, FormStateInterface $form_state) {

    // Add 1 to the number of names.
    $num_to_generate = $form_state->get('num_to_generate');
    $form_state->set('num_to_generate', ($num_to_generate + 1));

    // Rebuild the form.
    $form_state->setRebuild();
  }

  /**
   * Handle submit.
   */
  private function finalSubmit(array &$form, FormStateInterface $form_state) {

    // Path to the batch processing.
    $path = \Drupal::service('extension.list.module')->getPath('site_settings');
    $path .= '/src/SiteSettingsReplicateBatches.php';

    // Information to pass to the batch processing.
    $settings = [
      'values' => $form_state->getValues(),
    ];

    $batch = [
      'title' => $this->t('Exporting'),
      'operations' => [
        ['_site_settings_replicate_process_batch', [$settings]],
      ],
      'finished' => '_site_settings_replicate_finish_batch',
      'file' => $path,
    ];
    batch_set($batch);
  }

}
