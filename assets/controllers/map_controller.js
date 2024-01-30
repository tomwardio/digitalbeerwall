import { Controller } from '@hotwired/stimulus';

import L from 'leaflet';
import chroma from 'chroma-js';
import * as topojson from 'topojson-client';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    removedCountries: Array,
    countries: Array,
    countryNames: String,
    signatures: Array,
    countryTopo: String,
    mapboxToken: String,
  }

  connect() {
    var countries = {};
    var country_names = JSON.parse(this.countryNamesValue);
    var signatures = {};
    var removedCountries = this.removedCountriesValue;
    var selectedCountry = null;

    for (var country of removedCountries) {
      countries[country.code] = {
        name: country_names[country.code],
        numbeers: 0,
        url: '/country/' + country.code,
        removed: true,
        reason: country.reason
      }
    }

    var maxbeers = 0;

    for (var country of this.countriesValue) {
      if (!countries[country.country])
        countries[country.country] = {};
      countries[country.country].name = country_names[country.country];
      countries[country.country].numbeers = country.numbeers;
      countries[country.country].url = '/country/' + country.country;
      if (!countries[country.country].removed)
        countries[country.country].removed = false;
      maxbeers = Math.max(country.numbeers, maxbeers);
    }

    for (var signature of this.signaturesValue) {
      signatures[signature.country] = {
        name: signature.name,
        img_url: '/beer/' + signature.id + '/image'
      }
    }

    // Initialize Leaflet map
    var map = L.map('map-div', {
      center: [49.009952, 2.548635],
      minZoom: 2,
      maxZoom: 6
    });
    map.setView([49.009952, 2.548635], 3)
    map.setMaxBounds([[-90, -180], [90, 180]])

    // Add map tile layer
    L.tileLayer('https://api.mapbox.com/styles/v1/mapbox/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
      continuousWorld: false,
      noWrap: true,
      id: 'light-v11',
      accessToken: this.mapboxTokenValue
    }).addTo(map);

    // Register TopoJSON support to leaflet
    L.TopoJSON = L.GeoJSON.extend({
      addData: function (jsonData) {
        if (jsonData.type === "Topology") {
          for (let key in jsonData.objects) {
            var geojson = topojson.feature(jsonData, jsonData.objects[key]);
            L.GeoJSON.prototype.addData.call(this, geojson);
          }
        }
        else {
          // only add overlays for countries that have beers
          if (countries[jsonData.id]) {
            L.GeoJSON.prototype.addData.call(this, jsonData);
          }
        }
      }
    });

    // Register click function for deselecting a country
    map.on({
      click: resetHighlight
    });

    // Add info control
    var info = L.control();

    info.onAdd = function (map) {
      this._div = L.DomUtil.create('div', 'map-info text-dark');
      this.update();
      return this._div;
    };

    // method that we will use to update the control based on feature properties passed
    info.update = function (feature) {
      var info = null;
      var signature = null;
      if (feature) {
        info = countries[feature.id];
        signature = signatures[feature.id];
      }
      var html = '<h2>Beers per Country</h2>';
      if (info) {
        html += '<a href="' + info["url"] + '">';
        html += '<b>' + info["name"] + '</b>';
        html += '</a>';
        html += '<br>Total: ' + info["numbeers"];
        if (info.removed) {
          html += '<br><b>Removed Reason:</b>';
          html += '<br>' + info["reason"];
        }
        else {
          if (signature) {
            html += '<br>Signature:';
            html += '<a href="' + info["url"] + '">';
            html += '<div class="row">';
            html += '<div class="col-4">'
            html += '<div class="thumbnail">';
            html += '<img src="' + signature["img_url"] + '" style="width:100%; height:100%;object-fit: cover">';
            html += '</div>'; // image div
            html += '</div>'; // col div
            html += '<div class="col-8">'
            html += '<small>' + signature["name"] + '</small>';
            html += '</div>'; // col div
            html += '</div>'; // row div
            html += "</a>";
          }
        }
      }
      else {
        var num_countries = Object.keys(countries).length;
        var total_countries = Object.keys(country_names).length
        var num_removed = Object.keys(removedCountries).length;
        html += 'Total Countries: ' + ((num_countries / total_countries) * 100).toFixed(2) + '%';
        if (num_removed) {
          var valid_regions_percent = ((num_countries / (total_countries - num_removed)) * 100).toFixed(2);
          html += '<br>'
          html += 'Beer Drinking Regions: ' + valid_regions_percent + '%';
        }
      }
      this._div.innerHTML = html;
    };

    info.addTo(map);

    // Add legend
    var legend = L.control({ position: 'bottomright' });
    var grades = [1, 5, 10, 20];

    function getColor(d) {
      var colorScale = chroma.scale(['#D5E3FF', '#003171']).domain([0, 1]);
      return d > grades[3] ? colorScale(1.0).hex() :
        d > grades[2] ? colorScale(0.66).hex() :
          d > grades[1] ? colorScale(0.33).hex() :
            colorScale(0).hex();
    }

    legend.onAdd = function (map) {

      var div = L.DomUtil.create('div', 'map-info legend text-dark');

      // loop through our density intervals and generate a label with a colored square for each interval
      for (var i = 0; i < grades.length; i++) {
        div.innerHTML +=
          '<i style="background:' + getColor(grades[i] + 1) + '"></i> ' +
          grades[i] + (grades[i + 1] ? '&ndash;' + grades[i + 1] + '<br>' : '+');
      }

      return div;
    };

    legend.addTo(map);

    // Now create topo layer
    var topoLayer = new L.TopoJSON();

    function style(feature) {
      var numbeers = 0;
      var removed = false;
      var country = countries[feature.id];
      if (country) {
        numbeers = countries[feature.id]['numbeers'];
        removed = countries[feature.id].removed;
      }
      var fillColor = getColor(numbeers);
      var opacity = numbeers ? 0.7 : 0.0;

      if (removed) {
        fillColor = '#ff0000';
        opacity = 0.4;
      }

      return {
        fillColor: fillColor,
        fillOpacity: opacity,
        color: '#555',
        weight: 1,
        opacity: 0.5
      };
    }

    function highlightLayer(e) {
      // make sure we clear any currently selected country
      resetHighlight(e);

      var layer = e.target;
      selectedCountry = layer;

      layer.setStyle({
        weight: 5,
        color: '#666',
      });

      if (!L.Browser.ie && !L.Browser.opera) {
        layer.bringToFront();
      }
      info.update(layer.feature);
      L.DomEvent.stopPropagation(e);
    }

    function resetHighlight(e) {
      if (selectedCountry) {
        selectedCountry.setStyle(
          style(selectedCountry.feature));
        selectedCountry = null;
      }
      info.update();
    }

    function handleLayer(layer) {
      layer.on({
        //mouseover: highlightLayer,
        //mouseout: resetHighlight,
        click: highlightLayer
      });
    }

    fetch(this.countryTopoValue).then(data => data.json()).then(function (data) {
      topoLayer.addData(data);
      topoLayer.eachLayer(handleLayer);
      topoLayer.setStyle(style);
      topoLayer.addTo(map);
    });
  }
}