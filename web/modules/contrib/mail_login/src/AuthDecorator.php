<?php

namespace Drupal\mail_login;

use Drupal\user\UserAuthInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Validates user authentication credentials.
 */
class AuthDecorator implements UserAuthInterface {
  use DependencySerializationTrait;

  /**
   * The original user authentication service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a UserAuth object.
   *
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The original user authentication service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_managerk
   *   The entity type manager.
   */
  public function __construct(UserAuthInterface $user_auth, EntityTypeManagerInterface $entity_type_manager) {
    $this->userAuth = $user_auth;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($username, $password) {
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->get('mail_login.settings');

    // If we have an email lookup the username by email.
    if ($config->get('mail_login_enabled') && !empty($username)) {
      if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $account_search = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $username]);
        if ($account = reset($account_search)) {
          $username = $account->getAccountName();
          if(user_is_blocked($username)) {
            \Drupal::messenger()->addError(t('The user has not been activated yet or is blocked.'));
            return NULL;
          }
        }
      }
      // Check if login by email only option is enabled.
      else if ($config->get('mail_login_email_only')) {
        // Display a custom login error message.
        \Drupal::messenger()->addError(
          t('Login by username has been disabled. Use your email address instead.')
        );
        return NULL;
      }
    }
    return $this->userAuth->authenticate($username, $password);
  }

}
