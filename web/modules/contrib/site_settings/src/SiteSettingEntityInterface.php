<?php

namespace Drupal\site_settings;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Site Setting entities.
 *
 * @ingroup site_settings
 */
interface SiteSettingEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Site Setting type.
   *
   * @return string
   *   The Site Setting type.
   */
  public function getType();

  /**
   * Gets the Site Setting name.
   *
   * @return string
   *   Name of the Site Setting.
   */
  public function getName();

  /**
   * Sets the Site Setting name.
   *
   * @param string $name
   *   The Site Setting name.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The called Site Setting entity.
   */
  public function setName($name);

  /**
   * Gets the Site Setting creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Site Setting.
   */
  public function getCreatedTime();

  /**
   * Sets the Site Setting creation timestamp.
   *
   * @param int $timestamp
   *   The Site Setting creation timestamp.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The called Site Setting entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Site Setting published status indicator.
   *
   * Unpublished Site Setting are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Site Setting is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Site Setting.
   *
   * @param bool $published
   *   TRUE to set this Site Setting to published, FALSE to set it to
   *   unpublished.
   *
   * @return \Drupal\site_settings\SiteSettingEntityInterface
   *   The called Site Setting entity.
   */
  public function setPublished($published);

}
