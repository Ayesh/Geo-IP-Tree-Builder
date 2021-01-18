<?php

use Ayesh\GeoIPTreeBuilder\Builder;

require __DIR__ . '/vendor/autoload.php';

$builder = new Builder(
    dataPath: $argv[1] ?? 'input/GeoLite2-Country-Blocks-IPv4.csv',
    locationListPath: $argv[2] ?? 'input/GeoLite2-Country-Locations-en.csv',
    target_dir: $argv[3] ?? __DIR__ . '/../Geo-IP-Database/data'
);

$builder->run();
