import { Controller } from '@hotwired/stimulus';

import $ from 'jquery';
import Highcharts from 'highcharts';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    beersByDate: Array,
  }
  connect() {
    var dates = [];
    var num_beers = [];
    var totals = [];
    var current_total = 0;
    for (var entry of this.beersByDateValue) {
      var date = new Date(entry.dateadded);
      dates.push(date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: '2-digit' }));
      num_beers.push(entry.numbeers);
      current_total += entry.numbeers;
      totals.push(current_total);
    }

    Highcharts.chart('chart',{
      chart: {
        zoomType: 'x'
      },
      title: {
        text: 'Total Number of Beers Added'
      },

      xAxis: [{
        categories: dates,
        crosshair: true
      }],
      yAxis: [{ // Primary yAxis
        labels: {
        },
        title: {
          text: 'Total Beers',
        }
      }, { // Secondary yAxis
        title: {
          text: 'Beers per Day',
        },
        opposite: true
      }],
      tooltip: {
        shared: true
      },
      legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'top',
        floating: true,
      },
      series: [{
        name: 'Number of Beers',
        type: 'column',
        yAxis: 1,
        data: num_beers
      }, {
        name: 'Total Beers',
        type: 'spline',
        data: totals
      }]
    });
  }
}