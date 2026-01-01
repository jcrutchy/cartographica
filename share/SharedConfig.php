<?php

/*

SharedConfig.php
================

Purpose:
Load and cache shared configuration from the external data directory.
Provides a clean interface for retrieving config values.

Usage:
use cartographica\share\SharedConfig;
$webRoot=SharedConfig::get("web_root");
$from=SharedConfig::get("smtp_from_email");
$admin=SharedConfig::get("admin_email");

*/

namespace cartographica\share;

use Exception;

class SharedConfig
{
    private static ?array $cache = null;

    private static function load(): void
    {
        if (self::$cache !== null) {
            return; // already loaded
        }

        $path = CARTOGRAPHICA_SHARED_CONFIG;

        if (!file_exists($path)) {
            throw new \RuntimeException("Shared config not found: $path");
        }

        $json = file_get_contents($path);
        self::$cache = json_decode($json, true);
    }

    public static function get(string $key, $default = null)
    {
        self::load();

        return self::$cache[$key] ?? $default;
    }
}
