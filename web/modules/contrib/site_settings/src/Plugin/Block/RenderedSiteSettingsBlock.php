<?php

namespace Drupal\site_settings\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a 'RenderedSiteSettingsBlock' block.
 *
 * @Block(
 *  id = "single_rendered_site_settings_block",
 *  admin_label = @Translation("Rendered site settings block"),
 * )
 */
class RenderedSiteSettingsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Drupal\Core\Entity\EntityTypeManagerInterface.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'setting' => NULL,
      'label_display' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // Allow selection of a site settings entity type.
    $form['setting'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'site_setting_entity_type',
      '#title' => $this->t('Site setting type'),
      '#weight' => '20',
      '#required' => TRUE,
    ];
    if (isset($this->configuration['setting']) && !empty($this->configuration['setting'])) {
      $setting_entity_type = $this->entityTypeManager
        ->getStorage('site_setting_entity_type')
        ->load($this->configuration['setting']);
      $form['setting']['#default_value'] = $setting_entity_type;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['setting'] = $form_state->getValue('setting');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function build() {
    $build = [];

    $entities = $this->entityTypeManager
      ->getStorage('site_setting_entity')
      ->loadByProperties(['type' => $this->configuration['setting']]);

    if (empty($entities)) {
      return $build;
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('site_setting_entity');

    // Loop through the entities and their fields.
    foreach ($entities as $entity) {

      $pre_render = $view_builder->view($entity, 'default');
      $render_output = \Drupal::service('renderer')->render($pre_render);

      $build[] = [
        '#markup' => $render_output,
      ];
    }

    return $build;
  }

}
