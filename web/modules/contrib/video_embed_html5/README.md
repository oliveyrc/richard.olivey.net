This module add support for embedding HTML5 videos into your website using Video Embed Field

## Installation

To install this module, do the following:

1. Download the Video Embed HTML5 module and follow the instruction for
      [installing contributed modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8).

## Usage

 1. Install module
 2. Add video embed field and enable "HTML5" provider
 3. Add link that ends with mp4/ogg/webm
 
## Generating thumbnails
There are two ways you can generate thumbnails for a HTML5 video:
1. On server side: using the [php_ffmpeg module](https://www.drupal.org/project/php_ffmpeg). This requires you to configure the module properly
and add the dependent binaries.
2. On client side: if, for some reason, you are unable to use the above (preferred) solution. The thumbnails can be created on page load.
 A new CANVAS object will be created which uses the first frame of the video as a thumbnail.
