<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.4',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.3',
        'type' => 'css',
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'freewall' => [
        'version' => '1.0.8',
    ],
    'spin' => [
        'version' => '0.0.1',
    ],
    'imagesloaded' => [
        'version' => '5.0.0',
    ],
    'ev-emitter' => [
        'version' => '2.1.2',
    ],
    'topojson' => [
        'version' => '3.0.2',
    ],
    'topojson-client' => [
        'version' => '3.1.0',
    ],
    'topojson-server' => [
        'version' => '3.0.1',
    ],
    'topojson-simplify' => [
        'version' => '3.0.3',
    ],
    'leaflet' => [
        'version' => '1.9.4',
    ],
    'leaflet/dist/leaflet.min.css' => [
        'version' => '1.9.4',
        'type' => 'css',
    ],
    'chroma-js' => [
        'version' => '2.4.2',
    ],
    'blueimp-load-image' => [
        'version' => '5.16.0',
    ],
    'highcharts' => [
        'version' => '11.4.0',
    ],
    'bootstrap-icons/font/bootstrap-icons.min.css' => [
        'version' => '1.11.3',
        'type' => 'css',
    ],
    'bootstrap' => [
        'version' => '5.3.3',
    ],
];
