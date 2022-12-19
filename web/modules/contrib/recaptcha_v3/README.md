CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module enables you to easily configure reCaptcha v3
and a fallback challenge (captcha/recaptcha v2 e.g).
In case user fails reCaptcha v3,
he can be prompted with an additional challenge to prove.
This is an ideal way to maximize security without any user friction.

We no more rely on the reCAPTCHA module for the use of the `recaptcha-php`
library which is included in this module, and make use of
Composer instead of keeping a duplicating code.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/recaptcha_v3

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/recaptcha_v3

REQUIREMENTS
------------

This module requires the following module:

 * Captcha (https://www.drupal.org/project/captcha)

This module requires the following library:

 * google/recaptacha (https://github.com/google/recaptcha)

RECOMMENDED MODULES
-------------------

 * reCAPTCHA (https://www.drupal.org/project/recaptcha):
   When enabled, reCAPTCHA v2 becomes available as fallback challenge.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

 * If not using Composer,
   install the google/recaptacha (https://github.com/google/recaptcha) library.

CONFIGURATION
-------------

 * Register reCAPTCHA v3 keys (https://www.google.com/recaptcha/admin/create).

   - The documentation for Google reCaptcha V3

     The documentation can be found here
     https://developers.google.com/recaptcha/docs/v3),
     with information regarding keys registration.

 * Create at least one action:

   - Populate action name

   - Choose score threshold

   - Select action on user verification fail

 * Use the action you created above as a challenge in captcha form settings.

MAINTAINERS
-----------

Current maintainers:
 * Denis (dench0) - https://www.drupal.org/u/dench0
 * Majid Ali Khan (majid.ali) - https://www.drupal.org/u/majidali
 * Fabien Leroux (B-Prod) - https://www.drupal.org/u/b-prod


The development of Drupal 8 version of this project has been sponsored by:
 * 1xINTERNET
