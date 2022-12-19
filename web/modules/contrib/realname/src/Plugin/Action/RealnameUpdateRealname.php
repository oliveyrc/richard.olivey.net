<?php

namespace Drupal\realname\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Update user real name.
 *
 * @Action(
 *   id = "realname_update_realname_action",
 *   label = @Translation("Update real name"),
 *   type = "user"
 * )
 */
class RealnameUpdateRealname extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    realname_update($account);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
