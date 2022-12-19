<?php

namespace Drupal\realname\Controller;

use Drupal\system\Controller\EntityAutocompleteController;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Site\Settings;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class RealnameAutocompleteController extends EntityAutocompleteController {

  /**
   * {@inheritdoc}
   */
  public function handleAutocomplete(Request $request, $target_type, $selection_handler, $selection_settings_key) {
    if ($target_type != 'user') {
      return parent::handleAutocomplete($request, $target_type, $selection_handler, $selection_settings_key);
    }

    $matches = [];
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));
      $selection_settings = $this->keyValue->get($selection_settings_key, FALSE);
      if ($selection_settings !== FALSE) {
        $selection_settings_hash = Crypt::hmacBase64(serialize($selection_settings) . $target_type . $selection_handler, Settings::getHashSalt());
        if ($selection_settings_hash !== $selection_settings_key) {
          throw new AccessDeniedHttpException('Invalid selection settings key.');
        }
      }
      else {
        throw new AccessDeniedHttpException();
      }

      $matches = $this->getMatches($selection_settings, $typed_string);
    }

    return new JsonResponse($matches);
  }

  /**
   * Gets matched labels based on a given search string.
   *
   * @param array $selection_settings
   *   An array of settings that will be passed to the selection handler.
   * @param string $string
   *   (optional) The label of the entity to query by.
   *
   * @return array
   *   An array of matched entity labels, in the format required by the AJAX
   *   autocomplete API (e.g. array('value' => $value, 'label' => $label)).
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the current user doesn't have access to the specified entity.
   */
  protected function getMatches(array $selection_settings, $string = '') {
    $matches = [];

    if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $include_anonymous = isset($selection_settings['include_anonymous']) ? $selection_settings['include_anonymous'] : TRUE;

      $connection = \Drupal::database();
      $query = $connection->select('users_field_data', 'u');
      $query->fields('u', ['uid']);
      $query->leftJoin('realname', 'rn', 'u.uid = rn.uid');
      if ($match_operator == 'CONTAINS') {
        $query->condition((new Condition('OR'))
          ->condition('rn.realname', '%' . $connection->escapeLike($string) . '%', 'LIKE')
          ->condition('u.name', '%' . $connection->escapeLike($string) . '%', 'LIKE')
        );
      }
      else {
        $query->condition((new Condition('OR'))
          ->condition('rn.realname', $connection->escapeLike($string) . '%', 'LIKE')
          ->condition('u.name', $connection->escapeLike($string) . '%', 'LIKE')
        );
      }
      if ($include_anonymous == FALSE) {
        $query->condition('u.uid', 0, '>');
      }
      $query->range(0, 10);
      $uids = $query->execute()->fetchCol();
      $accounts = User::loadMultiple($uids);

      /** @var \Drupal\user\Entity\User $account */
      foreach ($accounts as $account) {
        $matches[] = [
          'value' => $this->t('@realname (@id)',
                  [
                    '@realname' => $account->getDisplayName(),
                    '@id' => $account->id(),
                  ]),
          'label' => $this->t('@realname (@username)',
                   [
                     '@realname' => $account->getDisplayName(),
                     '@username' => $account->getAccountName(),
                   ]),
        ];
      }
    }
    return $matches;
  }

}
