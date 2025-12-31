<?php

namespace cartographica\services\island;

use cartographica\share\SharedConfig;

class Config
{
    private const BASE = CARTOGRAPHICA_DATA_DIR . "/services/island";

    public static function privateKey(): string {
        return self::BASE . "/island_private.pem";
    }

    public static function publicKey(): string {
        return self::BASE . "/island_public.pem";
    }

    public static function sqlitePath(): string {
        return self::BASE . "/island.sqlite";
    }

    public static function logFile(): string {
        return self::BASE . "/log/island.log";
    }

    public static function islandConfig(): string {
        return self::BASE . "/island_config.json";
    }

    public static function identityUrl(): string {
        return SharedConfig::get("identity_url");
    }

    public static function directoryUrl(): string {
        return SharedConfig::get("island_directory_url");
    }
}
