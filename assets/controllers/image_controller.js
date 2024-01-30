import { Controller } from '@hotwired/stimulus';

import loadImage from 'blueimp-load-image';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  connect() {
    if (document.getElementById('form_imgdata').value) {
      var beer_image = document.getElementById('beer_img');
      beer_image.setAttribute('src', document.getElementById('form_imgdata').value);
    }

    document.getElementById('form_image').onchange = function (e) {

      var file = e.target && e.target.files && e.target.files[0];
      var options = {
        maxWidth: 720,
        canvas: true
      };
      if (!file)
        return;

      loadImage.parseMetaData(file, function (data) {
        if (data.exif) {
          if (data.exif.get('Orientation')) {
            options.orientation = data.exif.get('Orientation');
          }
        }

        // load the image
        loadImage(file,
          function (img) {
            var image_data = img.toDataURL("image/jpeg");
            document.getElementById('beer_img').setAttribute('src', image_data);
            document.getElementById('form_imgdata').value = image_data;
          },
          options);
      });
    };
  }
}