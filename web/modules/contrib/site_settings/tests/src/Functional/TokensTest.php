<?php

namespace Drupal\Tests\site_settings\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test tokens.
 *
 * @group site_settings
 */
class TokensTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['site_settings_sample_data'];

  /**
   * Test multiple tokens.
   */
  public function testMultipleTokens() {
    $value = \Drupal::service('token')->replace('[site_settings:other--test_multiple_entries--1--value]');
    $this->assertSame('Test multiple entries content 2', $value);
  }

}
