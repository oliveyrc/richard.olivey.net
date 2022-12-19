<?php

namespace Drupal\Tests\smart_trim\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This class provides methods specifically for testing something.
 *
 * @group smart_trim
 */
class SmartTrimFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'test_page_test',
    'field',
    'filter',
    'text',
    'token',
    'token_filter',
    'smart_trim',
    'filter_test',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if installing the module, won't break the site.
   */
  public function testInstallation() {
    $session = $this->assertSession();
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Tests if uninstalling the module, won't break the site.
   */
  public function testUninstallation() {
    // Go to uninstallation page an uninstall smart_trim:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-smart-trim');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    // Confirm uninstall:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The selected modules have been uninstalled.');
    // Retest the frontpage:
    $this->drupalGet('<front>');
    // Ensure the status code is success:
    $session->statusCodeEquals(200);
    // Ensure the correct test page is loaded as front page:
    $session->pageTextContains('Test page text.');
  }

  /**
   * Tests if the token will not get cut off when using the.
   */
  public function testTokenNotCutOffTrimTypeCharacters() {
    $session = $this->assertSession();
    $display_repository = \Drupal::service('entity_display.repository');
    // Editing our "filter_test" format:
    $edit = [
      'edit-filters-token-filter-status' => 1,
      'edit-filters-filter-html-escape-status' => 0,
    ];
    $this->drupalGet('admin/config/content/formats/manage/filter_test');
    $this->submitForm($edit, 'Save configuration');
    // Edit formatter settings:
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('body', [
        'type' => 'smart_trim',
        'settings' => [
          'trim_length' => 10,
          'trim_type' => 'chars',
          'summary_handler' => 'trim',
          'trim_options' => [
            'replace_tokens' => TRUE,
          ],
        ],
      ])
      ->save();

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 1,
      'type' => 'article',
      'body' => [
        'value' => 'Test [node:content-type]',
        'format' => 'filter_test',
      ],
    ])->save();
    $this->drupalGet('/node/1');
    // @todo This might change to "Test Artic" in the future, because the
    // "trim_type" is a bit confusing, see
    // https://www.drupal.org/project/smart_trim/issues/3308868.
    $session->elementTextEquals('css', 'article > div > div > div:nth-child(2) > p', 'Test');
  }

  /**
   * Tests if the token will not get cut off when using the.
   */
  public function testTokenNotCutOffTrimTypeWords() {
    $session = $this->assertSession();
    $display_repository = \Drupal::service('entity_display.repository');
    // Editing our "filter_test" format:
    $edit = [
      'edit-filters-token-filter-status' => 1,
      'edit-filters-filter-html-escape-status' => 0,
    ];
    $this->drupalGet('admin/config/content/formats/manage/filter_test');
    $this->submitForm($edit, 'Save configuration');
    // Edit formatter settings:
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('body', [
        'type' => 'smart_trim',
        'settings' => [
          'trim_length' => 10,
          'trim_type' => 'words',
          'summary_handler' => 'trim',
          'trim_options' => [
            'replace_tokens' => TRUE,
          ],
        ],
      ])
      ->save();

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 1,
      'type' => 'article',
      'body' => [
        'value' => 'Test [node:content-type]',
        'format' => 'filter_test',
      ],
    ])->save();
    $this->drupalGet('/node/1');
    $session->elementTextEquals('css', 'article > div > div > div:nth-child(2) > p', 'Test Article');
  }

}
