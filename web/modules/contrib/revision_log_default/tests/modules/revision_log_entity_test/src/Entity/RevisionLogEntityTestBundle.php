<?php

namespace Drupal\revision_log_entity_test\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;

/**
 * Defines the Test entity bundle configuration entity.
 *
 * @ConfigEntityType(
 *   id = "revision_log_default_test_bundle",
 *   label = @Translation("Revision log entity test bundle"),
 *   config_prefix = "revision_log_default_test_bundle",
 *   bundle_of = "revision_log_default_test_entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   },
 * )
 */
class RevisionLogEntityTestBundle extends EntityTestBundle {

}
