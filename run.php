<?php

use Ayesh\GeoIPTreeBuilder\Builder;

require __DIR__ . '/vendor/autoload.php';

$builder = new Builder(
    dataPath: 'input/GeoLite2-Country-Blocks-IPv4.csv',
    locationListPath: 'input/GeoLite2-Country-Locations-en.csv',
    target_dir: 'data'
);

$builder->run();
