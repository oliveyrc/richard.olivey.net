/**
 * @file
 * Recaptcha v3 behaviors.
 */

(function ($, Drupal) {
  'use strict';

  function updateTokenElement(element) {
    // Wait for grecaptcha to be loaded.
    if (typeof grecaptcha === 'undefined') {
      var timer = setInterval(function() {
        if (typeof grecaptcha !== 'undefined' || !element) {
          clearInterval(timer);

          if (element) {
            doUpdateTokenElement(element);
          }
        }
      }, 500);
    }
    else {
      doUpdateTokenElement(element);
    }
  }

  function doUpdateTokenElement(element) {
    grecaptcha.ready(function () {
      if (!element) {
        return;
      }

      var $element = $(element);

      grecaptcha.execute(
          $element.data('recaptchaV3SiteKey'),
          {
            action: $element.data('recaptchaV3Action')
          }
      ).then(function (token) {
        $element.val(token);
        $element.trigger('change');
      });
    });
  }

  /**
   * Attach recaptcha response token from google with form.
   *
   * @type {{attach: Drupal.behaviors.reCaptchaV3.attach}}
   */
  Drupal.behaviors.reCaptchaV3 = {
    attach: function (context) {
      once('recaptcha-v3-token', '.recaptcha-v3-token', context).forEach(function (element) {

        updateTokenElement(element);

        // Update the recaptcha tokens every 90 seconds.
        // This seems to be the most robust way to always have valid recaptcha
        // tokens when you don't have control over how the forms are being
        // submitted. For example normal form submits are synchronous while
        // Google Recaptcha v3 is asynchonous.
        // A recaptcha token has a maximum lifetime of 120 seconds.
        // https://developers.google.com/recaptcha/docs/v3
        var interval = setInterval(function () {
          if (!element) {
            clearInterval(interval);
          }
          else {
            updateTokenElement(element);
          }
        }, 90000);
      });
    }
  };

})(jQuery, Drupal);
