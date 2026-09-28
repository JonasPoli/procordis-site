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
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'flowbite' => [
        'version' => '4.0.1',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'flowbite-datepicker' => [
        'version' => '2.0.0',
    ],
    'flowbite/dist/flowbite.min.css' => [
        'version' => '4.0.1',
        'type' => 'css',
    ],
    'photoswipe' => [
        'version' => '5.4.4',
    ],
    'photoswipe/lightbox' => [
        'version' => '5.4.4',
    ],
    'swiper' => [
        'version' => '14.2.0',
    ],
    'swiper/modules' => [
        'version' => '14.2.0',
    ],
    'swiper/swiper-bundle.min.css' => [
        'version' => '14.2.0',
        'type' => 'css',
    ],
    'sortablejs' => [
        'version' => '1.15.7',
    ],
    'photoswipe/dist/photoswipe.min.css' => [
        'version' => '5.4.4',
        'type' => 'css',
    ],
];
