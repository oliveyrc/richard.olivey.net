<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\site_settings\Entity\SiteSettingEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Site Setting entities.
 *
 * @ingroup site_settings
 */
class SiteSettingEntityListBuilder extends EntityListBuilder {

  /**
   * Variable to store all bundles for quick access.
   *
   * @var array
   */
  private $bundles = [];

  /**
   * Link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  private $linkGeneration;

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\site_settings\SiteSettingsRenderer definition.
   *
   * @var \Drupal\site_settings\SiteSettingsRenderer
   */
  protected $siteSettingsRender;

  /**
   * Drupal\site_settings\SiteSettingsLoaderInterface definition.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderInterface
   */
  private $siteSettingsLoader;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   Link generator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\site_settings\SiteSettingsRenderer $site_settings_reader
   *   Site settings renderer.
   * @param \Drupal\site_settings\SiteSettingsLoaderInterface $site_settings_loader
   *   The site settings loader service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, LinkGeneratorInterface $link_generator, EntityTypeManagerInterface $entity_type_manager, SiteSettingsRenderer $site_settings_reader, SiteSettingsLoaderInterface $site_settings_loader, RendererInterface $renderer) {
    parent::__construct($entity_type, $storage);
    $this->linkGeneration = $link_generator;
    $this->entityTypeManager = $entity_type_manager;
    $this->siteSettingsRender = $site_settings_reader;
    $this->siteSettingsLoader = $site_settings_loader;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container,
    EntityTypeInterface $entity_type
  ) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('link_generator'),
      $container->get('entity_type.manager'),
      $container->get('site_settings.renderer'),
      $container->get('site_settings.loader'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['fieldset'] = $this->t('Group');
    $header['value'] = $this->t('Value');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\site_settings\Entity\SiteSettingEntity $entity */
    $row['name'] = $this->linkGeneration->generate(
      $entity->label(),
      new Url(
        'entity.site_setting_entity.edit_form', [
          'site_setting_entity' => $entity->id(),
        ]
      )
    );
    $entity_bundle = $entity->bundle();
    if ($bundle = SiteSettingEntityType::load($entity_bundle)) {
      $row['fieldset'] = $bundle->fieldset;
    }
    else {
      $row['fieldset'] = $this->t('Unknown');
    }

    // Render the value of the field into the listing page.
    $row['value'] = '';
    $fields = $entity->getFields();
    foreach ($fields as $key => $field) {
      if (method_exists(get_class($field), 'getFieldDefinition')) {
        $row['value'] = $this->siteSettingsRender->renderField($field);
      }
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort($this->entityType->getKey('fieldset'))
      ->sort($this->entityType->getKey('id'));
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entity_type = $this->entityType->getBundleEntityType();
    $this->bundles = $this->entityTypeManager
      ->getStorage($entity_type)
      ->loadMultiple();
    $missing_bundles = array_keys($this->bundles);

    $variables['settings'] = $this->siteSettingsLoader->loadAll(TRUE);

    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There is no @label yet.',
        ['@label' => $this->entityType->getLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    ];

    // Storage for entities the user is allowed to create but is not allowed
    // to update. Used when site_settings_type_permissions submodule is enabled.
    $creatable_non_viewable_entities = [];
    $last_fieldset = FALSE;
    foreach ($this->load() as $entity) {
      // Get bundle type.
      $bundle_type = $entity->getType();
      $search = array_search($bundle_type, $missing_bundles);
      if ($search !== FALSE) {
        unset($missing_bundles[$search]);
      }

      // Add each site setting if the user has access.
      if ($entity->access('update') || $entity->access('delete')) {

        // Set fieldset separator.
        $fieldset = $entity->fieldset->getValue()[0]['value'];
        if ($fieldset != $last_fieldset) {
          $heading = [
            '#markup' => '<strong>' . $fieldset . '</strong>',
          ];
          $build['table']['#rows'][$fieldset] = [
            'name' => $this->renderer->render($heading),
            'fieldset' => '',
            'value' => '',
            'operations' => '',
          ];
          $last_fieldset = $fieldset;
        }

        // Add table rows.
        if ($row = $this->buildRow($entity)) {
          $build['table']['#rows'][$entity->id()] = $row;
        }
      }
      elseif ($entity->access('create')) {

        // User is allowed to create this site setting type but cannot view.
        $creatable_non_viewable_entities[$bundle_type] = $entity;
      }
    }

    // If we have site settings the user can create but not update.
    if ($creatable_non_viewable_entities) {

      // Add site settings that can be created.
      foreach ($creatable_non_viewable_entities as $bundle => $entity) {

        $url = new Url('entity.site_setting_entity.add_form', [
          'site_setting_entity_type' => $bundle,
        ]);
        if ($url->access()) {

          // Add link if user has access.
          $link = [
            '#type' => 'link',
            '#title' => $this->t('Create setting'),
            '#url' => $url,
            '#attributes' => ['class' => ['button']],
          ];

          array_unshift($build['table']['#rows'], [
            'name' => $this->linkGeneration->generate($entity->label(), $url),
            'fieldset' => $entity->fieldset->value,
            'value' => '',
            'operations' => $this->renderer->render($link),
          ]);
        }
      }

      // Add heading.
      $heading = [
        '#markup' => '<strong>' . $this->t('Settings where more can be created') . '</strong>',
      ];
      array_unshift($build['table']['#rows'], [
        'name' => $this->renderer->render($heading),
        'fieldset' => '',
        'value' => '',
        'operations' => '',
      ]);
    }

    // If we have site settings not yet created.
    if ($missing_bundles) {

      // Sort missing bundles alphabetically by fieldset and label.
      usort($missing_bundles, function ($a, $b) {
        if ($this->bundles[$a]->fieldset == $this->bundles[$b]->fieldset) {
          return ($this->bundles[$a]->label() >= $this->bundles[$b]->label()) ? -1 : 1;
        }
        return $this->bundles[$a]->fieldset >= $this->bundles[$b]->fieldset ? -1 : 1;
      });

      // Boolean to determine whether the 'Settings not yet created' title
      // should be shown.
      $has_access_to_not_yet_created = FALSE;
      foreach ($missing_bundles as $missing) {

        // Settings that have not yet been created rows.
        $url = new Url('entity.site_setting_entity.add_form', [
          'site_setting_entity_type' => $missing,
        ]);
        if ($url->access()) {
          $has_access_to_not_yet_created = TRUE;

          // Add link if user has access.
          $link = [
            '#type' => 'link',
            '#title' => $this->t('Create setting'),
            '#url' => $url,
            '#attributes' => ['class' => ['button']],
          ];
          array_unshift($build['table']['#rows'], [
            'name' => $this->linkGeneration->generate($this->bundles[$missing]->label(),
              $url),
            'fieldset' => $this->bundles[$missing]->fieldset,
            'value' => '',
            'operations' => $this->renderer->render($link),
          ]);
        }
      }

      // Not yet created title.
      if ($has_access_to_not_yet_created) {
        $heading = [
          '#markup' => '<strong>' . $this->t('Settings not yet created') . '</strong>',
        ];
        array_unshift($build['table']['#rows'], [
          'name' => $this->renderer->render($heading),
          'fieldset' => '',
          'value' => '',
          'operations' => '',
        ]);
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = [
      '#attributes' => ['class' => ['container-inline']],
    ];

    $operations = $this->getDefaultOperations($entity);
    $operations += $this->moduleHandler()
      ->invokeAll('entity_operation', [$entity]);
    $this->moduleHandler->alter('entity_operation', $operations, $entity);

    uasort($operations,
      '\Drupal\Component\Utility\SortArray::sortByWeightElement');

    $build['operations'] = [
      '#prefix' => '<div class="align-left clearfix">',
      '#type' => 'operations',
      '#links' => $operations,
      '#suffix' => '</div>',
    ];

    // Add new operation.
    $entity_bundle = $entity->bundle();
    if (isset($this->bundles[$entity_bundle]) && $this->bundles[$entity_bundle]->multiple) {
      $url = new Url('entity.site_setting_entity.add_form', [
        'site_setting_entity_type' => $entity_bundle,
      ]);

      if ($url->access()) {
        $build['add'] = [
          '#prefix' => '<div class="align-right">',
          '#type' => 'link',
          '#title' => $this->t('Add another'),
          '#url' => $url,
          '#attributes' => ['class' => ['button']],
          '#suffix' => '</div>',
        ];
      }
    }

    return $build;
  }

}
