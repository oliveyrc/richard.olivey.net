
# Site Settings and Labels Module Readme

This module provides a way to let clients manage settings you define without
affecting the configuration of the site (ie, as 'Content'). It does the
following:

- provides an interface for administrators to set up settings
- allows administrators to add one or more fields to each setting
- allows the administator to decide whether to allow multiple occurrences
  of the same setting
- allows the administrator to group settings together
- provides a simple interface for anyone with the new permissions to add, edit,
  and delete site settings content
- caches the data and efficiently makes it available in every twig template
- stores the configuration for each setting as config so site builders can
  version control the available settings and their fields while keeping the
  content added to each setting outside of version control
- makes the settings available as tokens for use anywhere tokens are used
- allows quick mass replication of settings and labels
- provides a simple block to output site settings, further customisable in
  templates as described below

## Example use cases

- You want to let a client add one or more social networks with links to
  their profile pages and you want to show that on a few templates such as the
  header and footer
- You want to let a client add some general settings to control how or where
  things are displayed
- You want to allow a client to specify some general labels or settings you use
  throughout your site
- You want to let a client add some general settings that they can reuse as
  tokens in for instance automated emails

## Installation

To install this module, place it in your modules folder and enable it on the
modules page.


## Configuration

Optionally change the variable name your twig files will receive the site settings into at
`/admin/config/site-settings/config`. By default, your variables are in `{{ dump(site_settings) }}`.


## How to set up your settings


1. Set up your settings and add fields to your settings
2. Use the settings in twig templates as desired or in classes or other modules
   as desired
3. Export your settings config
4. Deploy your work to your production site
5. Choose your desired permissions in the admin > people > permissions tab to
   grant access to site editors for instance.

## How to access the settings in twig templates

Debug your settings when debug is enabled <https://www.drupal.org/docs/8/theming/twig/debugging-twig-templates>
via `{{ dump(site_settings) }}` or `{{ dump(site_settings.your_settings_group.your_setting_name) }}` for instance.

Access a non-repeating variable with one field like so:
`{{ site_settings.your_settings_group.your_setting_name }}`

Access a non-repeating variable with multiple fields like so:
`{{ site_settings.your_settings_group.your_setting_name.field_title }}` and
`{{ site_settings.your_settings_group.your_setting_name.field_subtitle }}`

Access a non-repeating variable with multiple fields and complex field settings:
`{{ site_settings.your_settings_group.your_setting_name.field_date.value }}`
`{{ site_settings.your_settings_group.your_setting_name.field_date.options }}` etc

Access a repeating variable with one field like so:
`{% for site_setting in site_settings.your_settings_group.your_setting_name %}
  {{ site_setting }}
{% endfor %}`

## How to access the settings in php files

Use the site_settings.loader service:
`$site_settings = \Drupal::service('site_settings.loader');
$settings = $site_settings->loadAll();`
or
`$site_settings = \Drupal::service('site_settings.loader');
$site_settings->loadByFieldset('your_settings_group');`

## How to access the settings via the token browser

Open the token browser anywhere and you'll find the settings are globally
avaiable under `Site settings and labels`.

## How to replicate settings rapidly

Browse to the manage settings page and choose the 'replicate' operation
from the setting you wish to use as the base for replications. Add as
many rows as desired and specify the new machine names, labels, and
how you would like the settings grouped.

## Feedback on this module

Please add issues with feature requests as well as feedback on the existing
functionality.

## Supporting organizations

Initial development of this module was sponsored by Fat Beehive until mid-2018.

## Maintainers

- Scott Euser (scott_euser) - <https://www.drupal.org/u/scott_euser>
