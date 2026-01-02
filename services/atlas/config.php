<?php

namespace cartographica\services\atlas;

use cartographica\share\SharedConfig;

class Config extends SharedConfig
{
    private const BASE = CARTOGRAPHICA_DATA_DIR . "/services/atlas";

    public static function privateKey(): string {
        return self::BASE . "/atlas_private.pem";
    }

    public static function publicKey(): string {
        return self::BASE . "/atlas_public.pem";
    }

    public static function sqlitePath(): string {
        return self::BASE . "/atlas.sqlite";
    }

    public static function logFile(): string {
        return self::BASE . "/log/atlas.log";
    }
}
