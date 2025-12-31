<?php

namespace cartographica\services\islanddirectory;

use cartographica\share\Db as SharedDb;

class Db
{
    public static function conn()
    {
        return SharedDb::connect(
            Config::sqlitePath(),
            __DIR__ . "/schema.sql"
        );
    }
}
