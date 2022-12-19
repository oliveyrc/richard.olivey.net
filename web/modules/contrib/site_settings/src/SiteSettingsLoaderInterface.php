<?php

namespace Drupal\site_settings;

/**
 * Provides an interface for a site settings loader.
 */
interface SiteSettingsLoaderInterface {

  /**
   * Load site settings by fieldset.
   *
   * @param string $fieldset
   *   The name of the fieldset.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   All settings within the given fieldset.
   */
  public function loadByFieldset($fieldset, $langcode = NULL);

  /**
   * Load site settings by fieldset.
   *
   * @param bool $rebuild_cache
   *   Force rebuilding of the cache by setting to true.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   All settings.
   */
  public function loadAll($rebuild_cache = FALSE, $langcode = NULL);

  /**
   * Rebuild the cache.
   */
  public function rebuildCache($langcode);

  /**
   * Clear the cache.
   */
  public function clearCache();

}
