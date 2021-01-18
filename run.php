<?php

use Ayesh\GeoIPTreeBuilder\Builder;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}
elseif (file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require __DIR__ . '/../../../vendor/autoload.php';
}
else {
    echo 'Could not run autoloader';
    die(1);
}


$builder = new Builder(
    dataPath: $argv[1] ?? 'input/GeoLite2-Country-Blocks-IPv4.csv',
    locationListPath: $argv[2] ?? 'input/GeoLite2-Country-Locations-en.csv',
    target_dir: $argv[3] ?? __DIR__ . '/../Geo-IP-Database/data'
);

$builder->run();
