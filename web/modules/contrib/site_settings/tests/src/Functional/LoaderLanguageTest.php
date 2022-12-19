<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests site settings translation.
 *
 * @group site_settings
 */
class LoaderLanguageTest extends BrowserTestBase {

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
    'language',
    'user',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp():void {
    parent::setUp();

    // Create the language.
    $language = ConfigurableLanguage::createFromLangcode('fr');
    $language->save();
  }

  /**
   * @covers ::lookupBySystemPath
   */
  public function testTranslation() {
    /** @var \Drupal\site_settings\SiteSettingsLoader $site_settings_loader */
    $site_settings_loader = \Drupal::service('site_settings.loader');

    // Add the translation.
    $site_settings = \Drupal::entityTypeManager()
      ->getStorage('site_setting_entity')
      ->loadByProperties(['type' => 'test_plain_text']);
    $site_setting = reset($site_settings);
    $original_value = $site_setting->get('field_testing')->value;
    $site_setting->set('field_testing', 'FR ' . $original_value);
    $site_setting->save();

    // Load the translations in the target language.
    $site_settings_translated = $site_settings_loader->loadAll(TRUE, 'fr');
    $this->assertSame('FR ' . $original_value, $site_settings_translated['other']['test_plain_text']);
  }

}
