<?php

namespace Drupal\site_settings_sample_data\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Test the default loading of site settings.
 *
 * @package Drupal\site_settings_sample_data\Controller
 */
class TestSiteSettingsLoader extends ControllerBase {

  /**
   * Dump site settings output results.
   */
  public function dump() {
    $build = [];

    $build['test'] = [
      '#type' => 'markup',
      '#markup' => 'Test123',
    ];

    // Load the site settings into the specified key.
    $site_settings_loader = \Drupal::service('site_settings.loader');
    $site_settings = $site_settings_loader->loadAll();

    // Test fieldsets match expectations. We don't care about the order.
    $expected = ['other', 'images', 'boolean'];
    $keys = array_keys($site_settings);
    sort($expected);
    sort($keys);
    if ($keys === $expected) {
      $build['fieldsets'] = [
        '#type' => 'markup',
        '#markup' => 'Fieldsets match expectations',
      ];
    }

    // Test plain text matches expectations.
    if ($site_settings['other']['test_plain_text'] == 'Test plain text value') {
      $build['test_plain_text'] = [
        '#type' => 'markup',
        '#markup' => 'Test plain text value is as expected',
      ];
    }

    // Test textarea matches expectations.
    if ($site_settings['other']['test_textarea'] == 'Test textarea value') {
      $build['test_textarea'] = [
        '#type' => 'markup',
        '#markup' => 'Test textarea value is as expected',
      ];
    }

    // Test multiple entries match expectations.
    if ($site_settings['other']['test_multiple_entries'][0] == 'Test multiple entries content 1') {
      $build['test_multiple_entries1'] = [
        '#type' => 'markup',
        '#markup' => 'Test multiple entries content 1 is as expected',
      ];
    }
    if ($site_settings['other']['test_multiple_entries'][1] == 'Test multiple entries content 2') {
      $build['test_multiple_entries2'] = [
        '#type' => 'markup',
        '#markup' => 'Test multiple entries content 2 is as expected',
      ];
    }

    // Test multiple entries and fields match expectations.
    if ($site_settings['other']['test_multiple_entries_and_fields'][0]['field_testing'] == 'Test multiple entries and fields content 1 field 1') {
      $build['test_multiple_entries_and_fields1_1'] = [
        '#type' => 'markup',
        '#markup' => 'Test multiple entries and fields content 1 field 1 is as expected',
      ];
    }
    if ($site_settings['other']['test_multiple_entries_and_fields'][0]['field_test_textarea'] == 'Test multiple entries and fields content 1 field 2') {
      $build['test_multiple_entries_and_fields1_2'] = [
        '#type' => 'markup',
        '#markup' => 'Test multiple entries and fields content 1 field 2 is as expected',
      ];
    }
    if ($site_settings['other']['test_multiple_entries_and_fields'][1]['field_testing'] == 'Test multiple entries and fields content 2 field 1') {
      $build['test_multiple_entries_and_fields2_1'] = [
        '#type' => 'markup',
        '#markup' => 'Test multiple entries and fields content 2 field 1 is as expected',
      ];
    }
    if ($site_settings['other']['test_multiple_entries_and_fields'][1]['field_test_textarea'] == 'Test multiple entries and fields content 2 field 2') {
      $build['test_multiple_entries_and_fields2_2'] = [
        '#type' => 'markup',
        '#markup' => 'Test multiple entries and fields content 2 field 2 is as expected',
      ];
    }

    // Test multiple fields match expectations.
    if ($site_settings['other']['test_multiple_fields']['field_testing'] == 'Test multiple fields field 1') {
      $build['test_multiple_fields1_1'] = [
        '#type' => 'markup',
        '#markup' => 'Test multiple fields field 1 is as expected',
      ];
    }
    if ($site_settings['other']['test_multiple_fields']['field_test_textarea'] == 'Test multiple fields field 2') {
      $build['test_multiple_fields1_2'] = [
        '#type' => 'markup',
        '#markup' => 'Test multiple fields field 2 is as expected',
      ];
    }

    // Test image matches expectations.
    if (is_numeric($site_settings['images']['test_image']['target_id']) && $site_settings['images']['test_image']['target_id'] > 0) {
      $build['test_image_target_id'] = [
        '#type' => 'markup',
        '#markup' => 'Test image target id is as expected',
      ];
    }
    if ($site_settings['images']['test_image']['uri'] == 'public://druplicon.png') {
      $build['test_image_uri'] = [
        '#type' => 'markup',
        '#markup' => 'Test image uri is as expected',
      ];
    }
    if ($site_settings['images']['test_image']['alt'] == 'Test image alt') {
      $build['test_image_alt'] = [
        '#type' => 'markup',
        '#markup' => 'Test image alt is as expected',
      ];
    }

    // Test images image 1 matches expectations.
    if (is_numeric($site_settings['images']['test_images'][0]['target_id']) && $site_settings['images']['test_images'][0]['target_id'] > 0) {
      $build['test_images_image_1_target_id'] = [
        '#type' => 'markup',
        '#markup' => 'Test images image 1 target id is as expected',
      ];
    }
    if ($site_settings['images']['test_images'][0]['uri'] == 'public://druplicon.png') {
      $build['test_images_image_1_uri'] = [
        '#type' => 'markup',
        '#markup' => 'Test images image 1 uri is as expected',
      ];
    }
    if ($site_settings['images']['test_images'][0]['alt'] == 'Test image alt 1') {
      $build['test_images_image_1_alt'] = [
        '#type' => 'markup',
        '#markup' => 'Test images image 1 alt is as expected',
      ];
    }

    // Test images image 2 matches expectations.
    if (is_numeric($site_settings['images']['test_images'][1]['target_id']) && $site_settings['images']['test_images'][1]['target_id'] > 0) {
      $build['test_images_image_2_target_id'] = [
        '#type' => 'markup',
        '#markup' => 'Test images image 2 target id is as expected',
      ];
    }
    if ($site_settings['images']['test_images'][1]['uri'] == 'public://druplicon.png') {
      $build['test_images_image_2_uri'] = [
        '#type' => 'markup',
        '#markup' => 'Test images image 2 uri is as expected',
      ];
    }
    if ($site_settings['images']['test_images'][1]['alt'] == 'Test image alt 2') {
      $build['test_images_image_2_alt'] = [
        '#type' => 'markup',
        '#markup' => 'Test images image 2 alt is as expected',
      ];
    }

    // Test file matches expectations.
    if (is_numeric($site_settings['images']['test_file']['target_id']) && $site_settings['images']['test_file']['target_id'] > 0) {
      $build['test_file_target_id'] = [
        '#type' => 'markup',
        '#markup' => 'Test file target id is as expected',
      ];
    }

    // Test multiple boolean fields match expectations.
    if ($site_settings['boolean']['test_boolean'][0] == '1') {
      $build['test_boolean_1'] = [
        '#type' => 'markup',
        '#markup' => 'Test boolean 1 is as expected',
      ];
    }
    if ($site_settings['boolean']['test_boolean'][1] == '0') {
      $build['test_boolean_2'] = [
        '#type' => 'markup',
        '#markup' => 'Test boolean 2 is as expected',
      ];
    }

    ob_start();
    print '<pre>';
    print_r($site_settings);
    print_r(array_keys($site_settings));
    print_r($expected);
    print '</pre>';
    $build['test'] = [
      '#type' => 'markup',
      '#markup' => ob_get_clean(),
    ];

    return $build;
  }

}
