<?php

namespace Drupal\site_settings_type_permissions;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\site_settings\Entity\SiteSettingEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions for site_setting of different types.
 */
class SiteSettingTypePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SiteSettingPermissions constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(\Drupal::service('entity_type.manager'));
  }

  /**
   * Returns an array of site_settings type permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   *
   * @return array
   *   The site_settings type permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function siteSettingTypePermissionsList() {
    $perms = [];
    $site_settings_types = $this->entityTypeManager
      ->getStorage('site_setting_entity_type')->loadMultiple();

    // Generate site_setting permissions for all site_setting types.
    foreach ($site_settings_types as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of site_setting permissions for a given site_setting type.
   *
   * @param \Drupal\site_settings\Entity\SiteSettingEntityType $type
   *   The site_settings type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(SiteSettingEntityType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "view published $type_id site setting entities" => [
        'title' => $this->t('%type_name: View published site settings', $type_params),
      ],
      "view unpublished $type_id site setting entities" => [
        'title' => $this->t('%type_name: View unpublished site settings', $type_params),
      ],
      "create $type_id site setting" => [
        'title' => $this->t('%type_name: Create new site setting', $type_params),
      ],
      "edit $type_id site setting" => [
        'title' => $this->t('%type_name: Edit site setting', $type_params),
      ],
      "delete $type_id site setting" => [
        'title' => $this->t('%type_name: Delete site setting', $type_params),
      ],
    ];
  }

}
