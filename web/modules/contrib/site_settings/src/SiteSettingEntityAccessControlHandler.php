<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Site Setting entity.
 *
 * @see \Drupal\site_settings\Entity\SiteSettingEntity.
 */
class SiteSettingEntityAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The field type plugin manager service.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  private $entityTypeManager;

  /**
   * Constructs an access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\site_settings\SiteSettingEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished site setting entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published site setting entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit site setting entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete site setting entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {

    // If site setting is not multiple, then creating more is not allowed.
    if ($entity_bundle) {

      // Get bundle info.
      /** @var \Drupal\site_settings\Entity\SiteSettingEntityType $bundle */
      $bundle = $this->entityTypeManager
        ->getStorage($this->entityType->getBundleEntityType())
        ->load($entity_bundle);

      if (!$bundle->multiple) {
        // Get count of settings in the selected bundle.
        $count = $this->entityTypeManager
          ->getStorage('site_setting_entity')
          ->getQuery('AND')
          ->condition('type', $entity_bundle)
          ->accessCheck(TRUE)
          ->count()
          ->execute();

        if ($count) {
          return AccessResult::forbidden();
        }
      }
    }

    return AccessResult::allowedIfHasPermission($account, 'add site setting entities');
  }

}
