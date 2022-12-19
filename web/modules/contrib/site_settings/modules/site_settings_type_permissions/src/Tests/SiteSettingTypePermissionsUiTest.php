<?php

namespace Drupal\site_settings_type_permissions\Tests;

use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Site Settings type permissions.
 *
 * @group SiteSettings
 */
class SiteSettingTypePermissionsUiTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'site_settings',
    'site_settings_sample_data',
    'site_settings_type_permissions',
    'field_ui',
    'user',
  ];

  /**
   * Tests site settings type permissions for editor users.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSiteSettingsTypePermissions() {
    // Create an editor user for test.
    $editor = $this->createUser([
      'administer site configuration',
      'access site settings overview',
      'view published test_multiple_entries_and_fields site setting entities',
      'view unpublished test_multiple_entries_and_fields site setting entities',
      'create test_multiple_entries_and_fields site setting',
      'edit test_multiple_entries_and_fields site setting',
      'delete test_multiple_entries_and_fields site setting',
      'view published test_plain_text site setting entities',
      'view unpublished test_plain_text site setting entities',
      'create test_plain_text site setting',
      'edit test_plain_text site setting',
      'delete test_plain_text site setting',
    ]);

    $this->drupalLogin($editor);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the fieldsets match.
    $this->assertSession()->responseContains('<strong>Other</strong>');
    $this->assertSession()->responseNotContains('<strong>Images</strong>');

    // Make sure the test plain text is as expected.
    $this->assertSession()->pageTextContains('Test plain text');

    // Make sure the test multiple entries and fields contents are as expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 1');
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 2');

    $this->drupalLogout();

    // Create an edit only user for test.
    $edit_only = $this->drupalCreateUser([
      'administer site configuration',
      'access site settings overview',
      'edit test_multiple_entries_and_fields site setting',
      'edit test_plain_text site setting',
    ]);

    $this->drupalLogin($edit_only);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the fieldsets match.
    $this->assertSession()->responseContains('<strong>Other</strong>');
    $this->assertSession()->responseNotContains('<strong>Images</strong>');

    // Make sure the test plain text is as expected.
    $this->assertSession()->pageTextContains('Test plain text');

    // Make sure the test multiple entries and fields contents are as expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 1');
    $this->assertSession()->pageTextContains('Test multiple entries and fields name 2');

    // Click edit link.
    $this->clickLink('Edit', 0);
    $this->assertSession()->pageTextContains('Edit Test plain text name');

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Click edit link.
    $this->clickLink('Edit', 1);
    $this->assertSession()->pageTextContains('Edit Test multiple entries and fields name 1');

    $this->drupalLogout();

    // Create a creator user for test.
    $creator = $this->drupalCreateUser([
      'administer site configuration',
      'access site settings overview',
      'create test_multiple_entries_and_fields site setting',
      'create test_plain_text site setting',
    ]);

    $this->drupalLogin($creator);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the test plain text is not expected.
    $this->assertSession()->pageTextNotContains('Test plain text');

    // Make sure the test multiple entries and fields contents are as expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields');

    // Click add link.
    $this->clickLink('Create setting', 0);

    // Make sure the test multiple entries and fields create page is as
    // expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields');

    // Open the site settings list page.
    $this->drupalGet('admin/structure/site_setting_entity/add/test_plain_text');
    $this->assertSession()->pageTextContains('Access denied');

    $this->drupalLogout();

    // Create a remover user for test.
    $remover = $this->drupalCreateUser([
      'administer site configuration',
      'access site settings overview',
      'view published test_multiple_entries_and_fields site setting entities',
      'view unpublished test_multiple_entries_and_fields site setting entities',
      'view published test_plain_text site setting entities',
      'view unpublished test_plain_text site setting entities',
      'delete test_multiple_entries_and_fields site setting',
      'delete test_plain_text site setting',
    ]);

    $this->drupalLogin($remover);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the test plain text is expected.
    $this->assertSession()->pageTextContains('Test plain text');

    // Make sure the test multiple entries and fields contents are as expected.
    $this->assertSession()->pageTextContains('Test multiple entries and fields');

    // Click delete link.
    $this->clickLink('Delete', 0);

    // Make sure the test plain text delete page is visible.
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->drupalGet($this->getUrl());

    // Click delete link.
    $this->submitForm([], 'Delete');

    // Make sure the test plain text is not expected.
    $this->assertSession()->pageTextNotContains('Test plain text value');

    $this->drupalLogout();

    // Login creator:
    $this->drupalLogin($creator);

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Make sure the test plain text is expected.
    $this->assertSession()->pageTextContains('Test plain text');
  }

}
