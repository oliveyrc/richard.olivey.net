CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Installation with Composer
* Configuration
* Maintainers


INTRODUCTION
------------

This module enables users to login by email address with the
minimal configurations.

 * For a full description of the module visit:
  https://www.drupal.org/project/mail_login

 * To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/mail_login


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the optimizely module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


INSTALLATION WITH COMPOSER
--------------------------

We recommend using Composer to download Mail Login module.
composer require 'drupal/mail_login:^2.0';


CONFIGURATION
--------------

Go to "/admin/config/people/accounts/mail-login" for the configuration screen,
   available configuraitons:
 * Enable login by email address: This option enables login by email address.
 * Override login form: This option allows you to override the login form
   username title/description.
 * Login form username title: Override the username field title.
 * Login form username description: Override the username field description.

MAINTAINERS
-----------

This module was created by mqanneh, a drupal developer.

 * Mohammad AlQanneh - https://www.drupal.org/u/mqanneh
