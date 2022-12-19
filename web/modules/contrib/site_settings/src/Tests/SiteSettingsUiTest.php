<?php

namespace Drupal\site_settings\Tests;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the loading of Site Settings.
 *
 * @group SiteSettings
 */
class SiteSettingsUiTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Module list.
   *
   * @var array
   */
  protected static $modules = [
    'site_settings',
    'site_settings_sample_data',
    'field_ui',
    'user',
  ];

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  private $adminUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp():void {
    parent::setUp();

    // Create the user and login.
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test site settings admin visibility.
   */
  public function testSiteSettingsAdminVisibility() {
    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');
    $session = $this->assertSession();

    // Make sure the fieldsets match.
    $session->responseContains('<strong>Images</strong>');
    $session->responseContains('<strong>Other</strong>');

    // Make sure the test plain text is as expected.
    $session->pageTextContains('Test plain text');

    // Make sure the test textarea is as expected.
    $session->pageTextContains('Test textarea name');

    // Make sure the test multiple entries contents are as expected.
    $session->pageTextContains('Test multiple entries');
    $session->pageTextContains('Test multiple entries name 2');

    // Make sure the test multiple entries and fields contents are as expected.
    $session->pageTextContains('Test multiple entries and fields name 1');
    $session->pageTextContains('Test multiple entries and fields name 2');

    // Make sure the test multiple fields contents are as expected.
    $session->pageTextContains('Test multiple fields name');

    // Make sure the test image is as expected.
    $session->pageTextContains('Test image');
    $session->pageTextContains('Test images 1');
    $session->pageTextContains('Test file');

  }

  /**
   * Test site settings add another.
   */
  public function testSiteSettingsAddAnother() {
    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');

    // Click add another link.
    $this->clickLink('Add another', 2);
    $session = $this->assertSession();

    // Make sure we can see the expected form.
    $session->pageTextContains('Test multiple entries');
    $session->pageTextContains('Testing');
    $params = [
      'field_testing[0][value]' => 'testSiteSettingsAddAnother',
    ];
    $this->submitForm($params, $this->t('Save'));
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Created the Test multiple entries Site Setting.');
    $session->pageTextContains('testSiteSettingsAddAnother');
  }

  /**
   * Test site settings edit existing.
   */
  public function testSiteSettingsEditExisting() {
    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');
    $session = $this->assertSession();

    // Click add another link.
    $this->clickLink('Edit', 5);
    $session = $this->assertSession();

    // Make sure we can see the expected form.
    $session->pageTextContains('Test plain text');
    $session->pageTextContains('Testing');
    $params = [
      'field_testing[0][value]' => 'testSiteSettingsEditExisting',
    ];
    $this->submitForm($params, $this->t('Save'));
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Saved the Test plain text Site Setting.');
    $session->pageTextContains('testSiteSettingsEditExisting');
  }

  /**
   * Test site settings create new type and add a setting to that.
   */
  public function testSiteSettingsCreateNewTypeAndSetting() {
    // Open the site settings list page.
    $this->drupalGet('admin/structure/site_setting_entity_type/add');

    // Create the new site setting.
    $params = [
      'label' => 'testSiteSettingsCreateNewTypeAndSetting',
      'id' => 'testsitesettingscreatenew',
      'existing_fieldset' => 'Other',
    ];
    $this->submitForm($params, $this->t('Save'));
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Created the testSiteSettingsCreateNewTypeAndSetting Site Setting type.');

    // Add field.
    $this->drupalGet('admin/structure/site_setting_entity_type/testsitesettingscreatenew/edit/fields/add-field');
    $params = [
      'existing_storage_name' => 'field_testing',
      'existing_storage_label' => 'testSiteSettingsCreateNewTypeAndSettingLabel',
    ];
    $this->submitForm($params, $this->t('Save and continue'));

    // Save field settings.
    $params = [];
    $this->submitForm($params, $this->t('Save settings'));
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Saved testSiteSettingsCreateNewTypeAndSettingLabel configuration.');
    $session->pageTextContains('field_testing');

    // Open the site settings list page.
    $this->drupalGet('admin/content/site-settings');
    $session = $this->assertSession();

    // Click add another link.
    $this->clickLink('Create setting');
    $session->pageTextContains('testSiteSettingsCreateNewTypeAndSettingLabel');
    $params = [
      'field_testing[0][value]' => 'testSiteSettingsCreateNewTypeAndSettingValue',
    ];
    $this->submitForm($params, $this->t('Save'));
    $session = $this->assertSession();

    // Ensure we saved correctly.
    $session->pageTextContains('Created the testSiteSettingsCreateNewTypeAndSetting Site Setting.');
    $session->pageTextContains('testSiteSettingsCreateNewTypeAndSettingValue');
  }

}
