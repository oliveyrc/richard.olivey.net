<?php

namespace Drupal\Tests\video_embed_html5\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\video_embed_brightcove\Plugin\video_embed_field\Provider\Brightcove;
use Drupal\video_embed_html5\Plugin\video_embed_field\Provider\Html5;

/**
 * Test that URL parsing for the provider is functioning.
 *
 * @group video_embed_html5
 */
class ProviderUrlParseTest extends UnitTestCase {

  /**
   * @dataProvider urlsWithExpectedIds
   *
   * Test URL parsing works as expected.
   */
  public function testUrlParsing($url, $expected) {
    if (is_array($expected)) {
      $this->assertArrayEquals($expected, Html5::getIdFromInput($url));
    }
    else {
      $this->assertEquals($expected, Html5::getIdFromInput($url));
    }

  }

  /**
   * A data provider for URL parsing test cases.
   *
   * @return array
   *   An array of test cases.
   */
  public function urlsWithExpectedIds() {
    return [
      'HTML5 video' => [
        'https://www.html5rocks.com/en/tutorials/video/basics/devstories.mp4',
        [
          'https://www.html5rocks.com/en/tutorials/video/basics/devstories.mp4',
          'mp4'
        ],
      ],
      'No HTML5 video' => [
        'https://www.youtube.com/watch?v=8HVWitAW-Qg',
        FALSE,
      ],
    ];
  }
}
