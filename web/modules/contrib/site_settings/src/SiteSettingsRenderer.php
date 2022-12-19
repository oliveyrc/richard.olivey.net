<?php

namespace Drupal\site_settings;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * The site settings default renderer service.
 *
 * @package Drupal\site_settings
 */
class SiteSettingsRenderer {
  use StringTranslationTrait;

  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Default image width.
   *
   * @var int
   */
  protected $defaultImageWidth = 25;

  /**
   * Default image height.
   *
   * @var int
   */
  protected $defaultImageHeight = 25;

  /**
   * Constructor.
   */
  public function __construct(RendererInterface $renderer, EntityTypeManagerInterface $entityTypeManager) {
    $this->renderer = $renderer;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Set default image size output.
   *
   * @param int $width
   *   The max image width in pixels.
   * @param int $height
   *   The max image height in pixels.
   */
  public function setDefaultImageSizeOutput($width, $height) {
    $this->defaultImageWidth = $width;
    $this->defaultImageHeight = $height;
  }

  /**
   * Render the value of the added fields.
   *
   * @param object $field
   *   The field to render.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The rendered html markup.
   *
   * @throws \Exception
   */
  public function renderField($field) {

    // Get information about the field.
    $definition = $field->getFieldDefinition();
    $field_type = $definition->getType();

    // Depending on the type of field, decide how to render.
    switch ($field_type) {
      case 'image':
        return $this->renderImage($field);

      default:
        return $this->renderDefault($field, $field_type);

    }
  }

  /**
   * Render a small version of the image.
   *
   * @param object $field
   *   The field to render.
   *
   * @return string
   *   The rendered html markup.
   *
   * @throws \Exception
   */
  protected function renderImage($field) {
    if (is_object($field) && isset($field->entity)) {
      $build = [
        '#theme' => 'image_style',
        '#width' => $this->defaultImageWidth,
        '#height' => $this->defaultImageHeight,
        '#style_name' => 'thumbnail',
        '#uri' => $field->entity->getFileUri(),
      ];
    }
    else {
      $build['#plain_text'] = $this->t('(none)');
    }
    return $this->renderer->render($build);
  }

  /**
   * Render a normal text value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to render.
   * @param object $field_type
   *   The field type to render.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered html markup.
   *
   * @throws \Exception
   */
  protected function renderDefault(FieldItemListInterface $field, $field_type) {
    $view_builder = $this->entityTypeManager->getViewBuilder('site_setting_entity');
    $build = $view_builder->viewField($field, [
      'type' => $field_type,
      'label' => 'hidden',
    ]);
    return $this->renderer->render($build);
  }

}
