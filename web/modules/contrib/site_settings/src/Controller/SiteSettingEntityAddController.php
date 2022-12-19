<?php

namespace Drupal\site_settings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The site settings content creation controller.
 *
 * @package Drupal\site_settings\Controller
 */
class SiteSettingEntityAddController extends ControllerBase {

  /**
   * The site setting entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The site setting entity type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $typeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityStorageInterface $storage, EntityStorageInterface $type_storage) {
    $this->storage = $storage;
    $this->typeStorage = $type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('site_setting_entity'),
      $entity_type_manager->getStorage('site_setting_entity_type')
    );
  }

  /**
   * Displays add links for available bundles/types.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A render array for a list of the site_setting_entity bundles/types that
   *   can be added or if there is only one type/bunlde defined for the site,
   *   the function returns the add page for that bundle/type.
   */
  public function add(Request $request) {
    $types = $this->typeStorage->loadMultiple();
    if ($types && count($types) == 1) {
      $type = reset($types);
      return $this->addForm($type, $request);
    }
    if (count($types) === 0) {
      return [
        '#markup' => $this->t('You have not created any %bundle types yet. @link to add a new type.', [
          '%bundle' => 'Site Setting',
          '@link' => Link::fromTextAndUrl($this->t('Go to the type creation page'), Url::fromRoute('entity.site_setting_entity_type.add_form'))->toString(),
        ]),
      ];
    }
    return [
      '#theme' => 'site_setting_entity_content_add_list',
      '#content' => $types,
    ];
  }

  /**
   * Presents the creation form for entities of given bundle/type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $site_setting_entity_type
   *   The custom bundle to add.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(EntityInterface $site_setting_entity_type, Request $request) {
    $entity = $this->storage->create([
      'type' => $site_setting_entity_type->id(),
    ]);
    return $this->entityFormBuilder()->getForm($entity);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\Core\Entity\EntityInterface $site_setting_entity_type
   *   The custom bundle/type being added.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(EntityInterface $site_setting_entity_type) {
    return $this->t('@label',
      ['@label' => $site_setting_entity_type->label()]
    );
  }

}
