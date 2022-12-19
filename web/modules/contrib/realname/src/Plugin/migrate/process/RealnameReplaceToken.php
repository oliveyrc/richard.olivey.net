<?php

namespace Drupal\realname\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * If the source evaluates to empty, we skip the current row.
 *
 * @MigrateProcessPlugin(
 *   id = "realname_replace_token",
 *   handle_multiples = TRUE
 * )
 */
class RealnameReplaceToken extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($realname_pattern) = $value;

    // Previous D7 realname token need to be replaced by D8 core token.
    //
    // At least two tokens may exists:
    // - [user:name-raw]
    // - [current-user:name-raw].
    return str_ireplace(':name-raw]', ':account-name]', $realname_pattern);
  }

}
