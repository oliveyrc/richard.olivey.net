<?php

namespace Drupal\Tests\addanother\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Addanother functionality.
 *
 * @group Addanother
 */
class AddAnotherTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static public $modules = ['addanother'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Tests Database Logging module functionality through interfaces.
   *
   * First creates content type, then logs in users, then creates nodes,
   * and finally tests Addanother module functionality through user interface.
   */
  public function testAddanother() {
    $node_type = $this->randomMachineName(8);
    $config = \Drupal::service('config.factory')->getEditable('addanother.settings');
    $config
      ->set('button.' . $node_type, TRUE)
      ->set('message.' . $node_type, TRUE)
      ->set('tab.' . $node_type, TRUE)
      ->set('tab_edit.' . $node_type, TRUE)
      ->save();

    $settings = [
      'type' => $node_type,
      'name' => $node_type,
    ];
    $this->drupalCreateContentType($settings);

    $web_user = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'use add another',
      'administer add another',
    ]);
    $this->drupalLogin($web_user);

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalGet("node/add/$node_type");
    $this->submitForm($edit, 'Save');

    // Check that the node has been created.
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Verify the assertion: pageTextContains() for HTML responses, responseContains() for non-HTML responses.
    // The passed text should be HTML decoded, exactly as a human sees it in the browser.
    $this->assertSession()->pageTextContains(t('@post @title has been created.', [
      '@post' => $node_type,
      '@title' => $edit['title[0][value]'],
    ]));
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Verify the assertion: pageTextContains() for HTML responses, responseContains() for non-HTML responses.
    // The passed text should be HTML decoded, exactly as a human sees it in the browser.
    $this->assertSession()->pageTextContains(t('You may add another @type.', ['@type' => $node_type]));
    $this->assertSession()->linkExists('Add another');

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalGet("node/add/$node_type");
    $this->submitForm($edit, 'Save and add another');

    // Check that the node has been created.
    $this->assertSession()->addressEquals("node/add/$node_type");
  }

}
