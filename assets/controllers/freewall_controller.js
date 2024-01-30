import { Controller } from '@hotwired/stimulus';

import 'freewall';
import Spinner from 'spin';
import imagesLoaded from 'imagesloaded';

export default class extends Controller {
  connect() {
    var wall = new freewall("#freewall");
    var spinTarget = document.getElementById("spinner");
    var spinner = new Spinner().spin(spinTarget);

    imagesLoaded(document.querySelector('#freewall'), function (instance) {
      wall.reset({
        selector: '.brick',
        animate: true,
        cellW: 220,
        cellH: 'auto',
        onResize: function () {
          wall.fitWidth();
        }
      });

      spinner.stop();
      document.getElementById("freewall").removeAttribute("hidden");
      if (document.getElementById("pages")) {
        document.getElementById("pages").removeAttribute("hidden");
      }
      wall.fitWidth();
    });
  }
}
