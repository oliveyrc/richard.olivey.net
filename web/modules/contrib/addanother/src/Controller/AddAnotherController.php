<?php

namespace Drupal\addanother\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Controller routines for Add another routes.
 */
class AddAnotherController extends ControllerBase {

  /**
   * Takes the user to the node creation page for the type of a given node.
   */
  public function addAnotherGoTo(NodeInterface $node) {
    return $this->redirect('node.add', [
      'node_type' => $node->getType(),
    ]);
  }

  /**
   * Takes the user to the node creation page for the type of a given node.
   */
  public function addAnotherAccess(NodeInterface $node) {
    if (!$node->access('create')) {
      return AccessResult::forbidden();
    }

    $config = \Drupal::config('addanother.settings');
    $account = \Drupal::currentUser();
    $type = $node->getType();

    if (\Drupal::routeMatch()->getRouteName() == 'entity.node.edit_form' &&
      !$config->get('tab_edit.' . $type)) {
      return AccessResult::forbidden();
    }

    if ($config->get('tab.' . $type) &&
      $account->hasPermission('use add another')) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
