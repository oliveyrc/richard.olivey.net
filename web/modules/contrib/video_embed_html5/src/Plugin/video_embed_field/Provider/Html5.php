<?php

namespace Drupal\video_embed_html5\Plugin\video_embed_field\Provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\video_embed_field\ProviderPluginBase;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Html5 provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "html_5",
 *   title = @Translation("HTML5")
 * )
 */
class Html5 extends ProviderPluginBase {

  /** @var FFMpeg $phpFFMpeg */
  protected $phpFFMpeg;
  protected $fileSystem;
  protected $config;
  protected $videoUrl;
  protected $filename;
  protected $videoType;


  /**
   * Html5 constructor.
   * @param string $configuration
   * @param string $plugin_id
   * @param array $plugin_definition
   * @param \GuzzleHttp\ClientInterface $http_client
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct($configuration, $plugin_id, array $plugin_definition,
                              ClientInterface $http_client, ModuleHandlerInterface $module_handler,
                              FileSystemInterface $file_system, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $http_client);

    $this->phpFFMpeg = FALSE;
    if ($module_handler->moduleExists('php_ffmpeg')) {
      $this->phpFFMpeg = \Drupal::service('php_ffmpeg');
    }
    $this->fileSystem = $file_system;
    $this->config = $config_factory->get('video_embed_html5.config');

    // Set filename for thumbnail.
    list($video_url, $video_type) = $this->getVideoId();
    $this->videoUrl = $video_url;
    $this->videoType = $video_type;
    $this->filename = md5($video_url);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('module_handler'),
      $container->get('file_system'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    // @todo: test with colorbox formatter.
    return [
      '#theme' => 'video_embed_html5',
      '#src' => $this->videoUrl,
      '#type' => 'video/' . $this->videoType,
      '#autoplay' => $autoplay,
      '#provider' => 'local_html5',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function downloadThumbnail() {
    if ($this->phpFFMpeg) {
      $local_uri = $this->getLocalThumbnailUri();
      if (!file_exists($local_uri)) {
        $this->fileSystem->prepareDirectory($this->thumbsDirectory, FileSystemInterface::CREATE_DIRECTORY);
        try {
          // Thumb does not exist yet. Generate it.
          $video = $this->phpFFMpeg->open($this->videoUrl);
          $video->frame(TimeCode::fromSeconds(1))
            ->save($this->fileSystem->realpath($this->thumbsDirectory) . '/' . $this->filename . '.jpg');
        } catch (\Exception $e) {
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri() {
    return $this->thumbsDirectory . '/' . $this->filename . '.jpg';
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    // Don't need this as we override "downloadThumbnail".
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function renderThumbnail($image_style, $link_url) {
    $build = parent::renderThumbnail($image_style, $link_url);

    if (!file_exists($this->getLocalThumbnailUri()) && !$this->phpFFMpeg) {
      // Set uri to default placeholder.
      $uri = drupal_get_path('module', 'video_embed_html5') . '/img/placeholder.png';
      if ($this->config->get('add_placeholder')) {
        if (($file = $this->config->get('placeholder')) && ($file = File::load($file[0]))) {
          // Custom placeholder is uploaded, use this one.
          /** @var FileInterface $file */
          $uri = $file->getFileUri();
        }
      }

      // Build render array for placeholder.
      $placeholder = [
        '#theme' => 'image',
        '#uri' => $uri,
      ];

      // Generate thumb in JS and render it as canvas.
      if ($link_url) {
        $build['#title'] = [
          '#type' => 'container',
          '#attributes' => [
            'data-render-thumbnail' => $this->videoUrl,
            'id' => 'video-embed-html5-' . uniqid(),
          ],
        ];

        if ($this->config->get('add_placeholder')) {
          $build['#title']['image'] = $placeholder;
        }
      }
      else {
        $build = [
          '#type' => 'container',
          '#attributes' => [
            'data-render-thumbnail' => $this->videoUrl,
            'id' => 'video-embed-html5-' . uniqid(),
          ],
        ];

        if ($this->config->get('add_placeholder')) {
          $build['image'] = $placeholder;
        }
      }

      // Attach lib to generate thumbnails.
      $build['#attached']['library'][] = 'video_embed_html5/thumbnails';

    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $pattern = "/(?:(\/)|(?:(?|http|https|ftp)(?|\/\/))).*(mp4|ogg|webm)/i";
    $matches = array();
    preg_match($pattern, $input, $matches);

    // Make sure there are values.
    if ($matches && isset($matches[2])) {
      return array($input, $matches[2]);
    }

    return FALSE;
  }
}
