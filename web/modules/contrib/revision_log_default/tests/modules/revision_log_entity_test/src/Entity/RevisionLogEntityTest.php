<?php

namespace Drupal\revision_log_entity_test\Entity;

use Drupal\entity_test_revlog\Entity\EntityTestWithRevisionLog;

/**
 * Defines a bundleable, revisionable, test entity.
 *
 * @ContentEntityType(
 *   id = "revision_log_default_test_entity",
 *   label = @Translation("Revision log entity test"),
 *   base_table = "revision_log_default_test_entity",
 *   revision_table = "revision_log_default_test_entity_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   bundle_entity_type = "revision_log_default_test_bundle",
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message"
 *   },
 * )
 */
class RevisionLogEntityTest extends EntityTestWithRevisionLog {

}
