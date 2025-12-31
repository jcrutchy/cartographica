<?php

namespace cartographica\services\islanddirectory;

use cartographica\share\SharedConfig;

class Config
{
    private const BASE = CARTOGRAPHICA_DATA_DIR . "/services/island-directory";

    public static function privateKey(): string {
        return self::BASE . "/island-directory_private.pem";
    }

    public static function publicKey(): string {
        return self::BASE . "/island-directory_public.pem";
    }

    public static function sqlitePath(): string {
        return self::BASE . "/island-directory.sqlite";
    }

    public static function logFile(): string {
        return self::BASE . "/log/island-directory.log";
    }

    public static function adminEmail(): string {
        return SharedConfig::get("admin_email");
    }
}
