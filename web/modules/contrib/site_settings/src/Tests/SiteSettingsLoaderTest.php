<?php

namespace Drupal\site_settings\Tests;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the loading of Site Settings.
 *
 * @group SiteSettings
 */
class SiteSettingsLoaderTest extends BrowserTestBase {

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
  protected $adminUser;

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    parent::setUp();

    // Create the user and login.
    $this->adminUser = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test site settings loader format.
   *
   * The site settings sample data controller compares arrays and outputs
   * statements to the browser if the arrays match our expections. In this way
   * we can catch if any changes to the code are modifying the output of the
   * array as that would result in a breaking change for users of this module.
   */
  public function testSiteSettingsLoaderFormat() {
    // Open the site settings sample data controller.
    $this->drupalGet('site_settings_sample_data/test_site_settings_loader');
    $session = $this->assertSession();

    // Make sure the fieldsets match.
    $session->pageTextContains('Fieldsets match expectations');

    // Make sure the test plain text is as expected.
    $session->pageTextContains('Test plain text value is as expected');

    // Make sure the test textarea is as expected.
    $session->pageTextContains('Test textarea value is as expected');

    // Make sure the test multiple entries contents are as expected.
    $session->pageTextContains('Test multiple entries content 1 is as expected');
    $session->pageTextContains('Test multiple entries content 2 is as expected');

    // Make sure the test multiple entries and fields contents are as expected.
    $session->pageTextContains('Test multiple entries and fields content 1 field 1 is as expected');
    $session->pageTextContains('Test multiple entries and fields content 1 field 2 is as expected');
    $session->pageTextContains('Test multiple entries and fields content 2 field 1 is as expected');
    $session->pageTextContains('Test multiple entries and fields content 2 field 2 is as expected');

    // Make sure the test multiple fields contents are as expected.
    $session->pageTextContains('Test multiple fields field 1 is as expected');
    $session->pageTextContains('Test multiple fields field 2 is as expected');

    // Make sure the test image is as expected.
    $session->pageTextContains('Test image target id is as expected');
    $session->pageTextContains('Test image alt is as expected');
    $session->pageTextContains('Test image uri is as expected');

    // Make sure the test images is as expected.
    $session->pageTextContains('Test images image 1 target id is as expected');
    $session->pageTextContains('Test images image 1 alt is as expected');
    $session->pageTextContains('Test images image 1 uri is as expected');
    $session->pageTextContains('Test images image 2 target id is as expected');
    $session->pageTextContains('Test images image 2 alt is as expected');
    $session->pageTextContains('Test images image 2 uri is as expected');

    // Make sure the test file is as expected.
    $session->pageTextContains('Test file target id is as expected');

    // Make sure the boolean field is as expected.
    $session->pageTextContains('Test boolean 1 is as expected');
    $session->pageTextContains('Test boolean 2 is as expected');
  }

  /**
   * Test site settings auto-load disabling.
   */
  public function testTemplateDontLoadSiteSettingsWhenDisabled() {
    // Disable auto-loading.
    $config = \Drupal::configFactory()->getEditable('site_settings.config');
    $config->set('disable_auto_loading', TRUE);
    $config->save();

    // Clear cache.
    drupal_flush_all_caches();

    $renderable = [
      '#theme' => 'test_site_settings_not_loaded',
    ];
    /** @var \Drupal\Core\Render\Renderer $renderer */
    $renderer = \Drupal::service('renderer');
    $rendered = $renderer->renderPlain($renderable);

    $this->assertEquals("Site settings were not automatically passed to the template (auto loading is off)", $rendered);
  }

}
