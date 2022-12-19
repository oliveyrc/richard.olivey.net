<?php

/**
 * @file
 * Contains site_setting_entity.page.inc.
 *
 * Page callback for Site Setting entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Site Setting templates.
 *
 * Default template: site_setting_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_site_setting_entity(array &$variables) {
  // Fetch SiteSettingEntity Entity Object.
  $site_setting_entity = $variables['elements']['#site_setting_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

/**
 * Prepares variables for a custom entity type creation list templates.
 *
 * Default template: site_setting_entity-content-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - content: An array of site_setting_entity-types.
 *
 * @see block_content_add_page()
 */
function template_preprocess_site_setting_entity_content_add_list(array &$variables) {
  $variables['types'] = [];
  $query = \Drupal::request()->query->all();
  foreach ($variables['content'] as $type) {
    $url = new Url(
      'entity.site_setting_entity.add_form',
      ['site_setting_entity_type' => $type->id()],
      ['query' => $query]
    );
    $link = Link::fromTextAndUrl($type->label(), $url);
    $variables['types'][$type->id()] = [
      'link' => $link,
      'description' => [
        '#markup' => $type->label(),
      ],
      'title' => $type->label(),
      'localized_options' => [
        'query' => $query,
      ],
    ];
  }
}
