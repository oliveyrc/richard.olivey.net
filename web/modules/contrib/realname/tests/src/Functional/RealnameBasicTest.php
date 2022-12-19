<?php

namespace Drupal\Tests\realname\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Test basic functionality of Realname module.
 *
 * @group Realname
 */
class RealnameBasicTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user to set up realname.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'realname',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer modules',
      'administer realname',
      'administer site configuration',
      'administer user fields',
      'administer user form display',
      'administer user display',
      'administer users',
    ];

    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test realname configuration.
   */
  public function testRealnameConfiguration() {
    $assert_session = $this->assertSession();

    // Check if Configure link is available on 'Modules' page.
    // Requires 'administer modules' permission.
    $this->drupalGet('admin/modules');
    // Assert that the configure link from Modules page to Realname settings
    // page exists.
    $assert_session->responseContains('admin/config/people/realname');

    // Check for setting page's presence.
    $this->drupalGet('admin/config/people/realname');
    // Assert the settings page is displayed.
    $assert_session->pageTextContains(t('Realname pattern'));

    // Save form with allowed token.
    $edit['realname_pattern'] = '[user:account-name]';
    $this->drupalGet('admin/config/people/realname');
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('admin/config/people/realname');
    $this->submitForm($edit, 'Save configuration');

    // Assert that the settings form has been saved.
    $assert_session->pageTextContains(t('The configuration options have been saved.'));

    // Check token recursion protection.
    $edit['realname_pattern'] = '[user:name]';
    $this->drupalGet('admin/config/people/realname');
    $this->submitForm($edit, 'Save configuration');
    // Assert that an invalid token is found.
    $assert_session->pageTextContains(t('The [user:name] token cannot be used as it will cause recursion.'));
  }

  /**
   * Test realname alter functions.
   */
  public function testRealnameUsernameAlter() {
    $assert_session = $this->assertSession();

    // Add a test string and see if core username has been replaced by realname.
    $edit['realname_pattern'] = '[user:account-name] (UID: [user:uid])';
    $this->drupalGet('admin/config/people/realname');
    $this->submitForm($edit, 'Save configuration');

    // Assert real name is shown on the user page.
    $this->drupalGet('user/' . $this->adminUser->id());
    $assert_session->pageTextContains($this->adminUser->getDisplayName());

    // Assert real name is shown on the user edit page.
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $assert_session->pageTextContains($this->adminUser->getDisplayName());

    /** @var \Drupal\user\Entity\User $user_account */
    $user_account = $this->adminUser;
    $username_before = $user_account->getAccountName();
    $user_account->save();
    $username_after = $user_account->getAccountName();
    $this->assertEquals($username_after, $username_before, 'Username did not change after save');
  }

  /**
   * Test realname display configuration.
   */
  public function testRealnameManageDisplay() {
    $assert_session = $this->assertSession();

    $edit['realname_pattern'] = '[user:account-name]';
    $this->drupalGet('admin/config/people/realname');
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('admin/config/people/accounts/fields');
    $assert_session->titleEquals('Manage fields | Drupal');
    // Assert real name field is not shown in manage fields list.
    $assert_session->pageTextNotContains('Real name');

    $this->drupalGet('admin/config/people/accounts/form-display');
    $this->assertSession()->titleEquals('Manage form display | Drupal');
    // Assert real name field is not shown in manage form display list.
    $assert_session->pageTextNotContains('Real name');

    $this->drupalGet('admin/config/people/accounts/display');
    $this->assertSession()->titleEquals('Manage display | Drupal');
    // Assert real name field is shown in manage display.
    $assert_session->pageTextContains('Real name');

    // By default the realname field is not visible.
    $this->drupalGet('user/' . $this->adminUser->id());
    // Assert real name field not visible on user page.
    $assert_session->pageTextNotContains('Real name');

    // Make realname field visible on user page.
    $this->drupalGet('admin/config/people/accounts/display');
    $edit = ['fields[realname][region]' => 'content'];
    $this->submitForm($edit, 'Save');
    $assert_session->statusCodeEquals(200);

    $this->drupalGet('user/' . $this->adminUser->id());
    // Assert real name field is visible on user page.
    $assert_session->pageTextContains('Real name');
  }

  /**
   * Test realname user update.
   */
  public function testRealnameUserUpdate() {
    $edit['realname_pattern'] = '[user:account-name]';
    $this->drupalGet('admin/config/people/realname');
    $this->submitForm($edit, 'Save configuration');

    $user1 = User::load($this->adminUser->id());
    $realname1 = $user1->realname;

    // Update user name.
    $user1->name = $this->randomMachineName();
    $user1->save();

    // Reload the user.
    $user2 = User::load($this->adminUser->id());
    $realname2 = $user2->realname;

    // Check if realname changed.
    $this->assertNotEmpty($realname1);
    $this->assertNotEmpty($realname2);
    $this->assertNotEquals($realname1, $realname2, '[testRealnameUserUpdate]: Real name changed.');
  }

}
