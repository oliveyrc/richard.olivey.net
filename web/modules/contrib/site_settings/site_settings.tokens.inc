<?php

/**
 * @file
 * Builds placeholder replacement tokens for node-related data.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function site_settings_token_info() {

  $types = [];
  $types['site_settings'] = [
    'name' => t("Site settings and labels"),
    'description' => t("Tokens for Site Settings and Labels."),
  ];

  $site_settings = [];

  // Dynamically generate the tokens for all available settings.
  $site_settings_loader = \Drupal::service('site_settings.loader');
  $settings = $site_settings_loader->loadAll();

  if ($settings) {
    foreach ($settings as $fieldset => $fieldtypes) {
      foreach ($fieldtypes as $fieldtype => $values) {
        $site_settings = _site_settings_build_token($site_settings, $fieldset, $fieldtype, $values);
      }
    }
  }

  return [
    'types' => $types,
    'tokens' => [
      'site_settings' => $site_settings,
    ],
  ];
}

/**
 * Build the tokens from the site settings.
 *
 * @param array $site_settings
 *   The site settings.
 * @param string $fieldset
 *   The fieldset.
 * @param string $fieldtype
 *   The field type.
 * @param mixed $values
 *   A single string value or array of values.
 * @param string $suffix
 *   A suffix to add to the token to handle array values.
 *
 * @return array
 *   The array of tokens.
 */
function _site_settings_build_token(array $site_settings, $fieldset, $fieldtype, $values, $suffix = FALSE) {
  if (is_array($values)) {

    // Recursively get all values.
    foreach ($values as $key => $values) {
      $this_suffix = ($suffix !== FALSE ? $suffix . '-' : '') . $key;
      $site_settings = _site_settings_build_token($site_settings, $fieldset, $fieldtype, $values, $this_suffix);
    }

  }
  else {

    // Set description based on whether flat or array of data.
    if ($suffix !== FALSE) {
      $description = t('One of the values of "@type" within "@fieldset"', [
        '@type' => $fieldtype,
        '@fieldset' => $fieldset,
      ]);
    }
    else {
      $description = t('The value of "@type" within "@fieldset"', [
        '@type' => $fieldtype,
        '@fieldset' => $fieldset,
      ]);
    }

    // Store each value or subvalue as a token option.
    $key = $fieldset . '--' . $fieldtype . ($suffix !== FALSE ? '--' . $suffix : '');
    $label = _site_settings_token_label($key);
    $site_settings[$key] = [
      'name' => t("@label", ['@label' => $label]),
      'description' => $description,
    ];
  }
  return $site_settings;
}

/**
 * Implements hook_tokens().
 */
function site_settings_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'site_settings') {

    // Allow developers to pass the settings to token replace so they can
    // modify them first potentially. If not passed, just make the defaults
    // available.
    if (!empty($data['site_settings'])) {
      $settings = $data['site_settings'];
    }
    else {
      $site_settings = \Drupal::service('site_settings.loader');
      $settings = $site_settings->loadAll();
    }

    foreach ($tokens as $name => $original) {
      $value = FALSE;

      // Break apart name.
      $name_parts = explode('--', $name);
      $fieldset = $name_parts[0];
      $type = $name_parts[1];

      // We may be deep into a multidimensional array.
      if (isset($name_parts[2]) && !empty($name_parts[2])) {

        // Break apart the on our separator to get the series of array keys we
        // need to navigate down.
        $additional_depth = $name_parts[2];
        $keys = explode('-', $additional_depth);
        if (isset($settings[$fieldset][$type])) {
          $setting = $settings[$fieldset][$type];
          $value = _site_settings_retrieve_deep_value($setting, $keys);
          $replacements[$original] = $value;
        }
      }
      elseif (isset($settings[$fieldset][$type])) {

        // Flat setting value.
        $value = $settings[$fieldset][$type];
      }

      // No setting value found.
      $replacements[$original] = $value;

    }

  }
  return $replacements;
}

/**
 * Retrieve a deep value from our multidimensional array.
 *
 * @param array|string $settings
 *   The settings data to be accessed.
 * @param array $keys
 *   A series of keys, the first one for the top level, the second one for the
 *   second level, etc.
 *
 * @return string
 *   The final value.
 */
function _site_settings_retrieve_deep_value($settings, array $keys) {
  if ($keys) {
    $next_key = array_shift($keys);
    if (isset($settings[$next_key]) && is_array($keys)) {
      return _site_settings_retrieve_deep_value($settings[$next_key], $keys);
    }
    else {
      return t('Setting not found');
    }
  }
  return $settings;
}

/**
 * Convert the token key into a more readable label.
 *
 * @param string $key
 *   The token key.
 *
 * @return string
 *   A more readable label.
 */
function _site_settings_token_label($key) {
  $label = str_replace(['--', '-', '_'], ' ', $key);
  $label = rtrim($label);
  $label = ucwords($label);
  return $label;
}
