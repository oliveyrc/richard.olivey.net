<?php

/**
 * @file
 * Hooks related to the Anonymous login module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters the list of included and excluded paths for redirection.
 *
 * Included paths are those that will redirect the user to the login page.
 *
 * Excluded paths are those that will not redirect the user.
 *
 * @param array $paths
 *   An array of paths, keyed with 'included' and 'excluded'.
 */
function hook_anonymous_login_paths_alter(array &$paths) {
  // Always include user test path.
  $paths['include'][] = '/test';

  // Never redirect on node paths.
  $paths['exclude'][] = '/node/*';
}

/**
 * @} End of "addtogroup hooks".
 */
