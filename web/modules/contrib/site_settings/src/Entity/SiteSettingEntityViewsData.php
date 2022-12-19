<?php

namespace Drupal\site_settings\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for Site Setting entities.
 */
class SiteSettingEntityViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['site_setting_entity_field_data']['table']['base'] = [
      'field' => 'id',
      'title' => $this->t('Site Setting'),
      'help' => $this->t('The Site Setting ID.'),
    ];

    return $data;
  }

}
