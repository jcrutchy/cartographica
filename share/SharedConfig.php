<?php

/*

SharedConfig.php
================

Purpose:
Load and cache shared configuration from the external data folder.
Provides a clean interface for retrieving config values.

Usage:
use cartographica\share\SharedConfig;
$webRoot=SharedConfig::get("web_root");
$from=SharedConfig::get("smtp_from_email");
$admin=SharedConfig::get("admin_email");

*/

namespace cartographica\share;

use Exception;

abstract class SharedConfig
{
    private static ?array $cache = null;

    protected static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = CARTOGRAPHICA_SHARED_CONFIG;

        if (!file_exists($path)) {
            throw new \RuntimeException("Shared config not found: $path");
        }

        $json = file_get_contents($path);
        self::$cache = json_decode($json, true);
        return self::$cache;
    }

    public static function get(string $key, $default = null)
    {
        $config = self::load();
        return $config[$key] ?? $default;
    }

    abstract public function privateKey(): string;

    abstract public function publicKey(): string;
}
