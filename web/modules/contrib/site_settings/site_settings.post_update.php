<?php

/**
 * @file
 * Post update functions for Site Settings.
 */

use Drupal\user\Entity\Role;

/**
 * Implements hook_post_update_NAME().
 *
 * Add the 'access site settings overview' permission for roles that have the
 * 'edit site setting entities' permission.
 */
function site_settings_post_update_update_permissions() {
  foreach (Role::loadMultiple() as $role) {
    if ($role->hasPermission('edit site setting entities')) {
      $role->grantPermission('access site settings overview')->save();
    }
  }
}
