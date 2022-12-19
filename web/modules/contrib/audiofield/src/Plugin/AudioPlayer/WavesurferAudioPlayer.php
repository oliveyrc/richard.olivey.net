<?php

namespace Drupal\audiofield\Plugin\AudioPlayer;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\audiofield\AudioFieldPluginBase;
use Drupal\Component\Serialization\Json;
use Drupal\file\Entity\File;
use Drupal\Core\Link;
use Drupal\Core\Asset\LibraryDiscovery;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the Wavesurfer Audio Player plugin.
 *
 * @AudioPlayer (
 *   id = "wavesurfer_audio_player",
 *   title = @Translation("Wavesurfer audio player"),
 *   description = @Translation("A customizable audio waveform visualization, built on top of Web Audio API and HTML5 Canvas."),
 *   fileTypes = {
 *     "mp3", "ogg", "oga", "wav",
 *   },
 *   libraryName = "wavesurfer",
 *   website = "https://github.com/katspaugh/wavesurfer.js",
 * )
 */
class WavesurferAudioPlayer extends AudioFieldPluginBase {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LibraryDiscovery $library_discovery, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory, FileSystemInterface $file_system, ModuleHandlerInterface $module_handler, FileUrlGenerator $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $library_discovery, $messenger, $logger_factory, $file_system, $file_url_generator);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('library.discovery'),
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('file_system'),
      $container->get('module_handler'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderPlayer(FieldItemListInterface $items, $langcode, array $settings) {
    // Check to make sure we're installed.
    if (!$this->checkInstalled()) {
      // Show the error.
      $this->showInstallError();

      // Simply return the default rendering so the files are still displayed.
      return $this->renderDefaultPlayer($items, $settings);
    }

    // Create arrays to pass to the twig template.
    $template_files = [];

    // Get a unique render Id.
    $settings['unique_id'] = $this->getUniqueRenderId();

    // Start building settings to pass to the javascript wavesurfer builder.
    $player_settings = [
      // Wavesurfer expects this as a 0 - 1 range.
      'volume' => ($settings['audio_player_initial_volume'] / 10),
      'playertype' => ($settings['audio_player_wavesurfer_combine_files'] ? 'playlist' : 'default'),
      'files' => [],
      'audioRate' => $settings['audio_player_wavesurfer_audiorate'],
      'autoCenter' => $settings['audio_player_wavesurfer_autocenter'],
      'backend' => $settings['audio_player_wavesurfer_backend'],
      'barGap' => $settings['audio_player_wavesurfer_bargap'],
      'barHeight' => $settings['audio_player_wavesurfer_barheight'],
      'barWidth' => $settings['audio_player_wavesurfer_barwidth'],
      'cursorColor' => $settings['audio_player_wavesurfer_cursorcolor'],
      'cursorWidth' => $settings['audio_player_wavesurfer_cursorwidth'],
      'forceDecode' => $settings['audio_player_wavesurfer_forcedecode'],
      'normalize' => $settings['audio_player_wavesurfer_normalize'],
      'progressColor' => $settings['audio_player_wavesurfer_progresscolor'],
      'responsive' => $settings['audio_player_wavesurfer_responsive'],
      'waveColor' => $settings['audio_player_wavesurfer_wavecolor'],
      'autoplayNextTrack' => $settings['audio_player_wavesurfer_playnexttrack'],
      'autoplay' => $settings['audio_player_autoplay'],
      'unique_id' => $settings['unique_id'],
    ];

    // Format files for output.
    $template_files = $this->getItemRenderList($items);
    foreach ($template_files as &$renderInfo) {
      // Generate settings for this file.
      $fileSettings = [
        'id' => $renderInfo->id,
        'path' => $renderInfo->url->toString(),
      ];

      // Check for Peak files.
      if ($settings['audio_player_wavesurfer_use_peakfile'] && $this->getClassType($renderInfo->item) == 'FileItem') {

        // Load the associated file.
        $file = File::load($renderInfo->item->get('target_id')->getCastedValue());
        // Get the file URL.
        $deliveryUrl = $file->getFileUri();
        // Get the file information so we can determin extension.
        $deliveryFileInfo = pathinfo($this->fileUrlGenerator->generateAbsoluteString($deliveryUrl));
        // Generate the URL for finding the peak file.
        $peakData = [
          'url' => dirname($deliveryUrl) . '/' . $deliveryFileInfo['filename'] . '.json',
          'arguments' => '--pixels-per-second 20 --bits 8',
        ];
        // Allow other modules to alter path data.
        $this->moduleHandler->alter('audiofield_wavesurfer_peak', $peakData);

        // Get the real path.
        $peakPath = $this->fileSystem->realpath($peakData['url']);

        // If the file is missing and Audiowaveform is installed.
        if (!file_exists($peakPath) && audiofield_check_audiowaveform_installed()) {

          $deliveryPath = escapeshellarg($this->fileSystem->realpath($deliveryUrl));
          $peakPath = escapeshellarg($peakPath);
          $peakArguments = $peakData['arguments'];

          // Generate the data file.
          shell_exec("audiowaveform -i $deliveryPath -o $peakPath $peakArguments");
          // If the file didn't generate, log/report the error.
          if (!file_exists($peakData['url'])) {
            $message_data = [
              '@status_report' => Link::createFromRoute('status report', 'system.status')->toString(),
            ];
            $this->loggerFactory->get('audiofield')->warning('Warning: Unable to generate Waveform peak file. Please check your installation of audiowaveform. More information available in the @status_report.', $message_data);
            $this->messenger->addWarning($this->t('Warning: Unable to generate Waveform peak file. Please check your installation of audiowaveform. More information available in the @status_report.', $message_data));
          }
        }

        // If the file exists, add it to the jQuery and template settings.
        if (file_exists($peakData['url'])) {
          $renderInfo->peakpath = $fileSettings['peakpath'] = $this->fileUrlGenerator->generateAbsoluteString($peakData['url']);
        }
      }

      // Add this file to the render settings.
      $player_settings['files'][] = $fileSettings;
    }

    return [
      'audioplayer' => [
        '#theme' => 'audioplayer',
        '#plugin_id' => 'wavesurfer',
        '#plugin_theme' => $player_settings['playertype'],
        '#settings' => $settings,
        '#files' => $template_files,
      ],
      'downloads' => $this->createDownloadList($items, $settings),
      '#attached' => [
        'library' => [
          // Attach the wavesurfer library.
          'audiofield/audiofield.' . $this->getPluginLibraryName(),
        ],
        'drupalSettings' => [
          'audiofieldwavesurfer' => [
            $settings['unique_id'] => $player_settings,
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLibraryVersion() {
    // Parse the JSON file for version info.
    $library_path = $this->fileSystem->realpath(DRUPAL_ROOT . $this->getPluginLibraryPath() . '/package.json');
    $library_data = Json::decode(file_get_contents($library_path));
    return $library_data['version'];
  }

}
