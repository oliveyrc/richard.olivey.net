<?php

namespace Drupal\site_settings\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\site_settings\SiteSettingEntityTypeInterface;

/**
 * Defines the Site Setting type entity.
 *
 * @ConfigEntityType(
 *   id = "site_setting_entity_type",
 *   label = @Translation("Site Setting type"),
 *   handlers = {
 *     "list_builder" = "Drupal\site_settings\SiteSettingEntityTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\site_settings\Form\SiteSettingEntityTypeForm",
 *       "edit" = "Drupal\site_settings\Form\SiteSettingEntityTypeForm",
 *       "delete" = "Drupal\site_settings\Form\SiteSettingEntityTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\site_settings\SiteSettingEntityTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "site_setting_entity_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "fieldset",
 *     "multiple",
 *   },
 *   admin_permission = "administer site configuration",
 *   bundle_of = "site_setting_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "fieldset" = "fieldset",
 *     "multiple" = "multiple",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/site_setting_entity_type/{site_setting_entity_type}",
 *     "add-form" = "/admin/structure/site_setting_entity_type/add",
 *     "edit-form" = "/admin/structure/site_setting_entity_type/{site_setting_entity_type}/edit",
 *     "delete-form" = "/admin/structure/site_setting_entity_type/{site_setting_entity_type}/delete",
 *     "collection" = "/admin/structure/site_setting_entity_type"
 *   }
 * )
 */
class SiteSettingEntityType extends ConfigEntityBundleBase implements SiteSettingEntityTypeInterface {

  /**
   * The Site Setting type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Site Setting type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Site Setting type fieldset.
   *
   * @var string
   */
  public $fieldset;

  /**
   * The Site Setting type multiple.
   *
   * @var bool
   */
  public $multiple;

}
