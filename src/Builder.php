<?php

namespace Ayesh\GeoIPTreeBuilder;

use InvalidArgumentException;
use IPv4\SubnetCalculator;
use RuntimeException;

class Builder {
    private array $locations = [];
    private array $processedData = [];

    private readonly string $dataPath;
    private readonly string $locationListPath;

    private readonly string $target_dir;

    private const int LOCATION_ID_POSITION = 0;
    private const int LOCATION_ISO_POSITION = 4;
    private const int IP_CIDR_POSITION = 0;
    private const int IP_LOCATION_POSITION = 1;

    public function __construct(string $dataPath, string $locationListPath, string $target_dir) {
        $this->dataPath = $dataPath;
        $this->locationListPath = $locationListPath;
        $this->target_dir = $target_dir;

        if (!file_exists($target_dir)) {
            throw new InvalidArgumentException('Target directory is not reachable');
        }
        if (!file_exists($dataPath)) {
            throw new InvalidArgumentException('IP range source file is unavailable.');
        }

        if (!file_exists($locationListPath)) {
            throw new InvalidArgumentException('Location source file is unavailable.');
        }
    }

    public function run(): void {
        $this->primeLocations();
        $this->parseDataFile();
        $this->writeDataFiles();
    }

    private function primeLocations(): void {
        $file = @fopen($this->locationListPath, 'rb');
        if (!$file) {
            throw new RuntimeException('Unable to open the locations file');
        }

        $headers = fgetcsv($file);
        if ($headers[static::LOCATION_ID_POSITION] !== 'geoname_id') {
            throw new InvalidArgumentException('Location source file is invalid: header-' . static::LOCATION_ID_POSITION . '. is not "geoname_id"');
        }
        if ($headers[static::LOCATION_ISO_POSITION] !== 'country_iso_code') {
            throw new InvalidArgumentException('Location source file is invalid: header-' . static::LOCATION_ISO_POSITION . '. is not "country_iso_code"');
        }

        while ($line = fgetcsv($file)) {
            $this->locations[$line[static::LOCATION_ID_POSITION]] = $line[static::LOCATION_ISO_POSITION];
        }
        fclose($file);
    }

    private function parseDataFile(): void {
        $file = @fopen($this->dataPath, 'rb');
        if (!$file) {
            throw new RuntimeException('Unable to open the data file');
        }

        $headers = fgetcsv($file);
        if ($headers[static::IP_CIDR_POSITION] !== 'network') {
            throw new InvalidArgumentException('Data source file is invalid: header-' . static::IP_CIDR_POSITION . '. is not "network"');
        }
        if ($headers[static::IP_LOCATION_POSITION] !== 'geoname_id') {
            throw new InvalidArgumentException('Data source file is invalid: header-' . static::IP_LOCATION_POSITION . '. is not "geoname_id"');
        }


        $last_country = null;
        $last_prefix = null;

        while ($line = fgetcsv($file)) {
            [$ip_cidr, $country_code] = [$line[static::IP_CIDR_POSITION], $line[static::IP_LOCATION_POSITION]];
            $country_code = $this->locations[$country_code] ?? $country_code;
            $prefix = explode('.', $ip_cidr, 2);

            $ip_cidr = explode('/', $ip_cidr);
            [$ip, $cidr] = $ip_cidr;

            $sub = new SubnetCalculator($ip, $cidr);
            $range = $sub->getIPAddressRange();
            $usage = explode('.', $range[0], 2);
            $storing_ip = ip2long('0.' . $usage[1]);

            $ip_parts = explode('.', $ip);

            $force_store = false;
            if (!isset($this->processedData[(int) $ip_parts[0]])) {
                $this->processedData[(int) $ip_parts[0]] = [];
                $force_store = true;
            }

            if ($last_country === $country_code && empty($force_store)) {
                continue;
            }

            $last_country = $country_code;

            $this->processedData[(int) $ip_parts[0]][(int) $storing_ip] = $this->locations[$country_code] ?? $country_code;
        }
    }

    private function writeDataFiles(): void {
        foreach ($this->processedData as $prefix => $data) {
            file_put_contents($this->target_dir . '/' . $prefix . '.json',
                              json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
            );
        }
    }
}
