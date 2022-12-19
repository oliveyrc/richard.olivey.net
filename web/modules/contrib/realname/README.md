# Realname

## CONTENTS OF THIS FILE

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


## INTRODUCTION

The Real name module allows the admin to choose fields from the user profile
that will be used to add a "realname" element (method) to a user object.
Hook_user is used to automatically add this to any user object that is loaded.


* For a full description of the module visit
  https://www.drupal.org/project/realname.

* To submit bug reports and feature suggestions, or to track changes visit
  https://www.drupal.org/project/issues/realname.


## REQUIREMENTS

This module requires the following module:

 * Token - https://www.drupal.org/project/token


## INSTALLATION

Install the Real name module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


## CONFIGURATION

The settings page is at Administration >> Configuration >> People >> Real name.

This pattern will be used to construct Realnames for all users.
Note that if the pattern is changed: all current Realnames will be deleted and
the list in the database will be rebuilt as needed.


## MAINTAINERS

* Alexander Hass (hass) - https://www.drupal.org/u/hass
