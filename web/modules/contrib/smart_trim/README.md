CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

 * For the description of the module visit:
   [project page](https://www.drupal.org/project/smart_trim)
   or
   [documentation page](https://www.drupal.org/docs/contributed-modules/smart-trim)

 * To submit bug reports and feature suggestions, or to track changes visit: 
   [issue queue](https://www.drupal.org/project/issues/smart_trim)

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

Install the Smart Trim module as you would normally install a contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further information.

CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module. The system
       breadcrumb block has now been updated.
    2. Navigate to /admin/structure/types/manage/**article**/display/**teaser**
      * Or any content type with a text, text_long, or text_with_summary field
      * Or any display mode. Typically teaser is trimmed text.
    3. In the format of the text field, select _Smart trimmed_
    4. Click the configuration wheel on the far right
    5. Update configuration with the following settings:
      * Trim length - Number of units to count.
      * Trim units - Characters or Words
      * Suffix - Charcters to display after trimmed text.
      * Wrap trimmed content - Adds a wrapper div to trimmed content.
      * Display more link? - Displays a link to the entity (if one exists)
      * Summary - Set configuration if Summary of field is used.
      * Strip HTML - Removes all HTML from field display
      * Honor a zero trim length
    6. Click the Update button.
    7. Save the Display form.

MAINTAINERS
-----------

 * Mark Casias - https://www.drupal.org/u/markie
 * AmyJune Hineline - https://www.drupal.org/u/volkswagenchick

Supporting organization:

 * Kanopi Studios - https://www.drupal.org/kanopi-studios
