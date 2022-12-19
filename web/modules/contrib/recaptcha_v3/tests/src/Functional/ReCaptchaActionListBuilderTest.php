<?php

namespace Drupal\Tests\recaptcha_v3\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Class ReCaptchaActionListBuilderTest.
 *
 * @package Drupal\Tests\recaptcha_v3\Functional
 *
 * @group recaptcha_v3
 */
class ReCaptchaActionListBuilderTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'captcha',
    'recaptcha_v3',
  ];

  /**
   * Test case for the recaptcha action list builder.
   */
  public function testListBuilder() {
    $add_form = Url::fromRoute('entity.recaptcha_v3_action.add_form');
    $collection = Url::fromRoute('entity.recaptcha_v3_action.collection');

    $assert = $this->assertSession();

    // Ensure anonymous access is denied to the add form.
    $this->drupalGet($add_form);
    $assert->statusCodeEquals(403);

    // Ensure anonymous access is denied to the collection form.
    $this->drupalGet($collection);
    $assert->statusCodeEquals(403);

    // Sign in as a captcha administrator.
    $this->drupalLogIn($this->createUser(['administer CAPTCHA settings']));
    $this->drupalGet($add_form);

    // Add an action.
    $this->submitForm([
      'label' => 'Test action',
      'id' => 'test_action',
      'threshold' => '.5',
      'challenge' => 'default',
    ], 'Save');

    // Check that the collection contains the new action.
    $this->drupalGet($collection);
    $assert->pageTextContains('Test action');
    $assert->pageTextContains('test_action');
    $assert->pageTextContains('.5');
    $assert->pageTextContains('Default');
  }

}
