/**
 * @file
 * Audiofield build Wavesurfer audio player.
 */

(($, Drupal) => {
  'use strict';

  Drupal.AudiofieldWavesurfer = {};

  /**
   * Generate a wavesurfer player.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {array} file
   *   The audio file for which we are generating a player.
   * @param {jQuery} settings
   *   The Drupal settings for this player..
   */
  Drupal.AudiofieldWavesurfer.generate = (context, file, settings) => {
    $.each($(context).find(`#${file.id}`).once('generate-waveform'), (index, wavecontainer) => {
      // Create waveform.
      const wavesurfer = WaveSurfer.create({
        container: `#${$(wavecontainer).attr('id')} .waveform`,
        backend: settings.backend,
        audioRate: settings.audioRate,
        autoCenter: settings.autoCenter,
        barGap: settings.barGap,
        barHeight: settings.barHeight,
        barWidth: settings.barWidth,
        cursorColor: settings.cursorColor,
        cursorWidth: settings.cursorWidth,
        forceDecode: settings.forceDecode,
        normalize: settings.normalize,
        progressColor: settings.progressColor,
        responsive: settings.responsive,
        waveColor: settings.waveColor,
      });

      // Check if a peak file has been provided.
      if (typeof file.peakpath !== 'undefined') {
        // Fetch the provided file.
        fetch(file.peakpath)
          .then(response => {
            if (!response.ok) {
              throw new Error("HTTP error " + response.status);
            }
            return response.json();
          })
          .then(peaks => {
            // Normalize the provided peaks data.
            Drupal.AudiofieldWavesurfer.normalizePeaks(peaks);

            // Load the file.
            wavesurfer.load(file.path, peaks.data);
            $(wavecontainer).find('.player-button.playpause').html('Play');
          })
          .catch((e) => {
            // Failed to load peaks, just load file normally.
            wavesurfer.load(file.path);
            console.error('error', e);
          });
      }
      else {
        // Load file without peaks.
        wavesurfer.load(file.path);
      }

      // Set the default volume.
      wavesurfer.setVolume(settings.volume);

      // Handle play/pause.
      $(wavecontainer).find('.player-button.playpause').on('click', (event) => {
        Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
      });

      // Handle volume change.
      $(wavecontainer).find('.volume').on('change', (event) => {
        wavesurfer.setVolume(($(event.currentTarget).val() / 10));
      });

      // Handle autoplay.
      if (!!settings.autoplay) {
        wavesurfer.on('ready', wavesurfer.play.bind(wavesurfer));
      }
      Drupal.AudiofieldWavesurfer.instance = wavesurfer;
    });
  };

  /**
   * Normalize a peak file
   *
   * @param {jQuery} peaks
   *   The Wavesurfer peaks data for which we are normalizing data.
   */
  Drupal.AudiofieldWavesurfer.normalizePeaks = function (peaks) {
    var max = peaks.data[0], min = peaks.data[0];
    var X, scale;
    for (X = 1; X < peaks.data.length; X++) {
      if (max < peaks.data[X]) {
        max = peaks.data[X];
      }
      if (min > peaks.data[X]) {
        min = peaks.data[X];
      }
    }
    scale = 1.0 / Math.max(Math.abs(min), Math.abs(max));
    for (X = 0; X < peaks.data.length; X++) {
      peaks.data[X] *= scale;
    }
  }

  /**
   * Generate a wavesurfer playlist player.
   *
   * @param {jQuery} context
   *   The Drupal context for which we are finding and generating this player.
   * @param {jQuery} settings
   *   The Drupal settings for this player.
   */
  Drupal.AudiofieldWavesurfer.generatePlaylist = (context, settings) => {
    $.each($(context).find(`#wavesurfer_playlist-${settings.unique_id}`).once('generate-waveform'), (index, wavecontainer) => {
      // Create waveform.
      const wavesurfer = WaveSurfer.create({
        container: `#${$(wavecontainer).attr('id')} .waveform`,
        audioRate: settings.audioRate,
        autoCenter: settings.autoCenter,
        barGap: settings.barGap,
        barHeight: settings.barHeight,
        barWidth: settings.barWidth,
        cursorColor: settings.cursorColor,
        cursorWidth: settings.cursorWidth,
        forceDecode: settings.forceDecode,
        normalize: settings.normalize,
        progressColor: settings.progressColor,
        responsive: settings.responsive,
        waveColor: settings.waveColor,
      });

      // Set the default volume.
      wavesurfer.setVolume(settings.volume);

      // Load the first file.
      const first = $(wavecontainer).find('.playlist .track').first();
      // Get the label and update it with the first filename.
      const label = $(wavecontainer).find('label').first();
      label.html(`Playing: ${first.html()}`);
      // Set the playing class on the first element.
      first.addClass('playing');

      // Load the file.
      Drupal.AudiofieldWavesurfer.Load(wavecontainer, wavesurfer, first, false);

      // Handle play/pause.
      $(wavecontainer).find('.player-button.playpause').on('click', (event) => {
        Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
      });

      // Handle next/previous.
      $(wavecontainer).find('.player-button.next').on('click', (event) => {
        Drupal.AudiofieldWavesurfer.Next(wavecontainer, wavesurfer);
      });
      $(wavecontainer).find('.player-button.previous').on('click', (event) => {
        Drupal.AudiofieldWavesurfer.Previous(wavecontainer, wavesurfer);
      });

      // Handle clicking track.
      $(wavecontainer).find('.playlist .track').on('click', (event) => {
        // Check if the track is already playing.
        if ($(this).hasClass('playing')) {
          // Play/pause the track if it is already loaded.
          Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
        }
        else {
          // Load the track.
          Drupal.AudiofieldWavesurfer.Load(wavecontainer, wavesurfer, $(event.currentTarget));
        }
      });

      // Handle volume change.
      $(wavecontainer).find('.volume').on('change', (event) => {
        wavesurfer.setVolume(($(event.currentTarget).val() / 10));
      });

      // Handle autoplay.
      if (!!settings.autoplay) {
        wavesurfer.on('ready', wavesurfer.play.bind(wavesurfer));
      }

      // Handle track finishing.
      if (settings.autoplayNextTrack) {
        wavesurfer.on('finish', (event) => {
          Drupal.AudiofieldWavesurfer.Next(wavecontainer, wavesurfer);
        });
      }
      Drupal.AudiofieldWavesurfer.instance = wavesurfer;
    });
  };

  /**
   * Play or pause the wavesurfer and set appropriate classes.
   *
   * @param {jQuery} wavecontainer
   *   The container of the wavesurfer element we are accessing.
   * @param {jQuery} wavesurfer
   *   The wavesurfer player we are accessing.
   */
  Drupal.AudiofieldWavesurfer.PlayPause = (wavecontainer, wavesurfer) => {
    wavesurfer.playPause();
    const button = $(wavecontainer).find('.player-button.playpause');
    if (wavesurfer.isPlaying()) {
      $(wavecontainer).addClass('playing');
      button.html('Pause');
    }
    else {
      $(wavecontainer).removeClass('playing');
      button.html('Play');
    }
  };

  /**
   * Load track on wavesurfer and set appropriate classes.
   *
   * @param {jQuery} wavecontainer
   *   The container of the wavesurfer element we are accessing.
   * @param {jQuery} wavesurfer
   *   The wavesurfer player we are accessing.
   * @param {jQuery} track
   *   The track being loaded into the player.
   */
  Drupal.AudiofieldWavesurfer.Load = (wavecontainer, wavesurfer, track, playonload = true) => {
    // Check for peak file.
    const peakpath = track.attr('data-peakpath');
    if (peakpath !== '') {
      // Fetch the peak file.
      fetch(peakpath)
        .then(response => {
          if (!response.ok) {
            throw new Error("HTTP error " + response.status);
          }
          return response.json();
        })
        .then(peaks => {
          // Normalize the peaks.
          Drupal.AudiofieldWavesurfer.normalizePeaks(peaks);

          // Load the file with peaks data.
          wavesurfer.load(track.attr('data-src'), peaks.data);
        })
        .catch((e) => {
          // Error loading peaks, load file normally.
          wavesurfer.load(track.attr('data-src'));
          console.error('error', e);
        });
    }
    else {
      // Load the track normally.
      wavesurfer.load(track.attr('data-src'));
    }

    wavesurfer.on('ready', (event) => {
      if (playonload) {
        $(wavecontainer).removeClass('playing');
        $(wavecontainer).addClass('playing');
        $(wavecontainer).find('.player-button.playpause').html('Pause');
        wavesurfer.play();
      }
    });
    // Remove playing from all other tracks.
    $(wavecontainer).find('.track').removeClass('playing');
    // Set the class on this track.
    track.addClass('playing');
    // Show what's playing.
    $(wavecontainer).find('label').first().html(`Playing: ${track.html()}`);
  };

  /**
   * Skip track forward on wavesurfer and set appropriate classes.
   *
   * @param {jQuery} wavecontainer
   *   The container of the wavesurfer element we are accessing.
   * @param {jQuery} wavesurfer
   *   The wavesurfer player we are accessing.
   */
  Drupal.AudiofieldWavesurfer.Next = (wavecontainer, wavesurfer) => {
    if (wavesurfer.isPlaying()) {
      Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
    }
    // Find the next track.
    let track = $(wavecontainer).find('.track.playing').next();
    if (typeof track.attr('data-src') === 'undefined') {
      track = $(wavecontainer).find('.track').first();
    }
    // Load the track.
    Drupal.AudiofieldWavesurfer.Load(wavecontainer, wavesurfer, track);
  };

  /**
   * Skip track back on wavesurfer and set appropriate classes.
   *
   * @param {jQuery} wavecontainer
   *   The container of the wavesurfer element we are accessing.
   * @param {jQuery} wavesurfer
   *   The wavesurfer player we are accessing.
   */
  Drupal.AudiofieldWavesurfer.Previous = (wavecontainer, wavesurfer) => {
    if (wavesurfer.isPlaying()) {
      Drupal.AudiofieldWavesurfer.PlayPause(wavecontainer, wavesurfer);
    }
    // Find the next track.
    let track = $(wavecontainer).find('.track.playing').prev();
    if (typeof track.attr('data-src') === 'undefined') {
      track = $(wavecontainer).find('.track').last();
    }
    // Load the track.
    Drupal.AudiofieldWavesurfer.Load(wavecontainer, wavesurfer, track);
  };

  /**
   * Attach the behaviors to generate the audio player.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches generation of Wavesurfer audio players.
   */
  Drupal.behaviors.audiofieldwavesurfer = {
    attach: (context, settings) => {
      $.each(settings.audiofieldwavesurfer, (key, settingEntry) => {
        // Default audio player.
        if (settingEntry.playertype === 'default') {
          // Loop over the files.
          $.each(settingEntry.files, (key2, file) => {
            Drupal.AudiofieldWavesurfer.generate(context, file, settingEntry);
          });
        }
        else if (settingEntry.playertype === 'playlist') {
          Drupal.AudiofieldWavesurfer.generatePlaylist(context, settingEntry);
        }
      });
    },
  };
})(jQuery, Drupal);
