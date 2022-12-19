<?php

namespace Drupal\audiofield\Commands;

use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\audiofield\AudioFieldPlayerManager;
use Drupal\Core\Archiver\ArchiverManager;

/**
 * A Drush commandfile for Audiofield module.
 */
class AudiofieldCommands extends DrushCommands {

  /**
   * Library discovery service.
   *
   * @var Drupal\audiofield\AudioFieldPlayerManager
   */
  protected $playerManager;

  /**
   * File system service.
   *
   * @var Drupal\Component\FileSystem\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Archive manager service.
   *
   * @var Drupal\Core\Archiver\ArchiverManager
   */
  protected $archiverManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(AudioFieldPlayerManager $player_manager, FileSystemInterface $file_system, ArchiverManager $archiver_manager) {
    parent::__construct();

    $this->playerManager = $player_manager;
    $this->fileSystem = $file_system;
    $this->archiverManager = $archiver_manager;
  }

  /**
   * Downloads the suggested Audiofield libraries from their remote repos.
   *
   * @param string $installLibrary
   *   The name of the library. If omitted, all libraries will be installed.
   * @param bool $print_messages
   *   Flag indicating if messages should be displayed.
   *
   * @command audiofield:download
   * @aliases audiofield-download
   */
  public function download($installLibrary = '', $print_messages = TRUE) {

    // Get a list of the audiofield plugins.
    $pluginList = $this->playerManager->getDefinitions();

    // If there is an argument, check to make sure its valid.
    if (!empty($installLibrary)) {
      if (!isset($pluginList[$installLibrary . '_audio_player'])) {
        $this->logger()->error(dt('Error: @library is not a valid Audiofield library.', [
          '@library' => $installLibrary,
        ]));
        return;
      }
      // If the argument is valid, we only want to install that plugin.
      $pluginList = [$installLibrary . '_audio_player' => $pluginList[$installLibrary . '_audio_player']];
    }

    // Loop over each plugin and make sure it's library is installed.
    foreach ($pluginList as $pluginName => $plugin) {
      // Create an instance of this plugin.
      $pluginInstance = $this->playerManager->createInstance($pluginName);

      // Only check install if there is a library for the plugin.
      if (!$pluginInstance->getPluginLibrary()) {
        continue;
      }

      // Skip if the plugin is installed.
      if ($pluginInstance->checkInstalled()) {
        if ($print_messages) {
          $this->logger()->notice(dt('Audiofield library for @library is already installed at @location', [
            '@library' => $pluginInstance->getPluginTitle(),
            '@location' => $pluginInstance->getPluginLibraryPath(),
          ]));
        }
        continue;
      }

      // Get the library install path.
      $path = DRUPAL_ROOT . $pluginInstance->getPluginLibraryPath();
      // Create the install directory if it does not exist.
      if (!is_dir($path)) {
        $this->fileSystem->mkdir($path);
      }

      // Download the file.
      $destination = $this->fileSystem->tempnam($this->fileSystem->getTempDirectory(), 'file.') . "tar.gz";
      system_retrieve_file($pluginInstance->getPluginRemoteSource(), $destination, FALSE);
      if (!file_exists($destination)) {
        // Remove the directory.
        $this->fileSystem->rmdir($path);
        $this->logger()->error(dt('Error: unable to download @library.', [
          '@library' => $pluginInstance->getPluginTitle(),
        ]));
        continue;
      }
      $this->fileSystem->move($destination, $path . '/audiofield-dl.zip');
      if (!file_exists($path . '/audiofield-dl.zip')) {
        // Remove the directory where we tried to install.
        $this->fileSystem->rmdir($path);
        if ($print_messages) {
          $this->logger()->error(dt('Error: unable to download Audiofield library @library', [
            '@library' => $pluginInstance->getPluginTitle(),
          ]));
          continue;
        }
      }

      // Unzip the file.
      $zipFile = $this->archiverManager->getInstance(['filepath' => $path . '/audiofield-dl.zip']);
      $zipFile->extract($path);

      // Remove the downloaded zip file.
      $this->fileSystem->unlink($path . '/audiofield-dl.zip');

      // If the library still is not installed, we need to move files.
      if (!$pluginInstance->checkInstalled()) {
        // Find all folders in this directory and move their
        // subdirectories up to the parent directory.
        $directories = $this->fileSystem->scanDirectory($path, '/.*?/', [
          'recurse' => FALSE,
        ]);
        foreach ($directories as $dirName) {
          $this->fileSystem->move($path . '/' . $dirName->filename, $this->fileSystem->getTempDirectory() . '/temp_audiofield', FileSystemInterface::EXISTS_REPLACE);
          $this->fileSystem->rmdir($path);
          $this->fileSystem->move($this->fileSystem->getTempDirectory() . '/temp_audiofield', $path, FileSystemInterface::EXISTS_REPLACE);
        }

        // Projekktor source files need to be installed.
        if ($pluginInstance->getPluginId() == 'projekktor_audio_player') {
          $process = Drush::process('npm install', $path);
          $process->run();
          $process = Drush::process('grunt --force', $path);
          $process->run();
        }
        // Wavesurfer source files need to be installed.
        if ($pluginInstance->getPluginId() == 'wavesurfer_audio_player') {
          $this->logger()->notice(dt('Installing @library', [
            '@library' => $pluginInstance->getPluginTitle(),
          ]));
          $process = Drush::process('npm install', $path);
          $process->run();
          $this->logger()->notice(dt('Building @library', [
            '@library' => $pluginInstance->getPluginTitle(),
          ]));
          $process = Drush::process('npm run build', $path);
          $process->run();
        }
      }
      if ($pluginInstance->checkInstalled()) {
        if ($print_messages) {
          $this->logger()->notice(dt('Audiofield library for @library has been successfully installed at @location', [
            '@library' => $pluginInstance->getPluginTitle(),
            '@location' => $pluginInstance->getPluginLibraryPath(),
          ]));
        }
      }
      else {
        // Remove the directory where we tried to install.
        $this->fileSystem->rmdir($path);
        if ($print_messages) {
          $this->logger()->error(dt('Error: unable to install Audiofield library @library', [
            '@library' => $pluginInstance->getPluginTitle(),
          ]));
        }
      }
    }
  }

  /**
   * Updates Audiofield libraries from their remote repos if out of date.
   *
   * @param string $updateLibrary
   *   The name of the library. If omitted, all libraries will be updated.
   * @param bool $print_messages
   *   Flag indicating if messages should be displayed.
   *
   * @command audiofield:update
   * @aliases audiofield-update
   */
  public function update($updateLibrary = '', $print_messages = TRUE) {

    // Get a list of the audiofield plugins.
    $pluginList = $this->playerManager->getDefinitions();

    // If there is an argument, check to make sure its valid.
    if (!empty($updateLibrary)) {
      if (!isset($pluginList[$updateLibrary . '_audio_player'])) {
        $this->logger()->error(dt('Error: @library is not a valid Audiofield library.', [
          '@library' => $updateLibrary,
        ]));
        return;
      }
      // If the argument is valid, we only want to install that plugin.
      $pluginList = [$updateLibrary . '_audio_player' => $pluginList[$updateLibrary . '_audio_player']];
    }

    // Loop over each plugin and make sure it's library is installed.
    foreach ($pluginList as $pluginName => $plugin) {
      // Create an instance of this plugin.
      $pluginInstance = $this->playerManager->createInstance($pluginName);

      // Only check install if there is a library for the plugin.
      if (!$pluginInstance->getPluginLibrary()) {
        continue;
      }

      // Get the library install path.
      $path = DRUPAL_ROOT . $pluginInstance->getPluginLibraryPath();

      // If the library isn't installed at all we just run the install.
      if (!$pluginInstance->checkInstalled(FALSE)) {
        $this->download($pluginInstance->getPluginLibraryName());
        continue;
      }

      // Don't updating the library if its up to date.
      if ($pluginInstance->checkVersion(FALSE)) {
        $this->logger()->notice(dt('Audiofield library for @library is already up to date', [
          '@library' => $pluginInstance->getPluginTitle(),
        ]));
        continue;
      }

      // Move the current installation to the temp directory.
      $this->fileSystem->move($path, $this->fileSystem->getTempDirectory() . '/temp_audiofield', TRUE);
      // If the directory failed to move, just delete it.
      if (is_dir($path)) {
        $this->fileSystem->rmdir($path);
      }

      // Run the install command now to get the latest version.
      $this->download($updateLibrary, FALSE);

      // Check if library has been properly installed.
      if ($pluginInstance->checkInstalled()) {
        // Remove the temporary directory.
        $this->fileSystem->rmdir($this->fileSystem->getTempDirectory() . '/temp_audiofield');
        $this->logger()->notice(dt('Audiofield library for @library has been successfully updated at @location', [
          '@library' => $pluginInstance->getPluginTitle(),
          '@location' => $pluginInstance->getPluginLibraryPath(),
        ]));
      }
      else {
        // Remove the directory where we tried to install.
        $this->fileSystem->rmdir($path);
        $this->logger()->error(dt('Error: unable to update Audiofield library @library', [
          '@library' => $pluginInstance->getPluginTitle(),
        ]));
        // Restore the original install since we failed to update.
        $this->fileSystem->move($this->fileSystem->getTempDirectory() . '/temp_audiofield', $path, TRUE);
      }
    }
  }

}
