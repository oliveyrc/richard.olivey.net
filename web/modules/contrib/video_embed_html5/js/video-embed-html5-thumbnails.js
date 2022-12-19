(function ($) {
  var mapping = {};
  $('div[data-render-thumbnail]').each(function (index) {
    $this = $(this);
    var id = $this.attr('id');
    // Create video element.
    var video = document.createElement('video');
    video.preload = 'auto';
    video.src = $this.attr('data-render-thumbnail');
    // Add to array of videos.
    mapping[id] = {video: video, element: $this};
  });

  $.each(mapping, function (index, value) {
    var video = value.video;
    $element = value.element;

    video.addEventListener('loadeddata', function () {
      // This fires the "seeked" event.
      video.currentTime = 10;
    }, false);

    video.addEventListener('seeked', function () {
      // Create new canvas of frame in video.
      var c = document.createElement("canvas");
      c.width = video.videoWidth;
      c.height = video.videoHeight;
      c.getContext('2d').drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
      // Add canvas to dom.
      $(mapping[index].element).html(c);

    }, false);
  });
})(jQuery);