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
$config=SharedConfig::all();

*/

namespace cartographica\share;

use Exception;

class SharedConfig
{
    private static ?array $config = null;

    /**
     * Load config.json once and cache it.
     */
    private static function load(): void
    {
        if (self::$config !== null) {
            return;
        }

        $path=CARTOGRAPHICA_SHARED_CONFIG;

        if (!file_exists($path)) {
            throw new Exception("Shared config file not found: $path");
        }

        $json = json_decode(file_get_contents($path), true);

        if (!is_array($json)) {
            throw new Exception("Invalid shared config JSON at: $path");
        }

        self::$config = $json;
    }

    /**
     * Retrieve a config value by key.
     */
    public static function get(string $key, $default = null)
    {
        self::load();
        return self::$config[$key] ?? $default;
    }

    /**
     * Retrieve the entire config array.
     */
    public static function all(): array
    {
        self::load();
        return self::$config;
    }
}
