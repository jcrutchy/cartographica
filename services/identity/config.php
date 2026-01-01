<?php

namespace cartographica\services\identity;

use cartographica\share\SharedConfig;

class Config
{
    private const BASE = CARTOGRAPHICA_DATA_DIR . "/services/identity";

    public static function privateKey(): string {
        return self::BASE . "/identity_private.pem";
    }

    public static function publicKey(): string {
        return self::BASE . "/identity_public.pem";
    }

    public static function sqlitePath(): string {
        return self::BASE . "/identity.sqlite";
    }

    public static function logFile(): string {
        return self::BASE . "/log/identity.log";
    }

    public static function loginLinkBase(): string {
        return SharedConfig::get("web_root") . "/client?token=";
    }

    public static function adminEmail(): string {
        return SharedConfig::get("admin_email");
    }

}
