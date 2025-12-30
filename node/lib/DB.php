<?php

class DB
{
    private static ?SQLite3 $db = null;
    private static bool $initialized = false;

    public static function init(string $path, string $schemaFile = null): void
    {
        if (self::$db !== null) {
            return;
        }

        self::$db = new SQLite3($path);
        self::$db->busyTimeout(5000);
        self::$db->exec("PRAGMA journal_mode = WAL;");
        self::$db->exec("PRAGMA synchronous = NORMAL;");

        if ($schemaFile !== null) {
            self::initSchema($schemaFile);
        }
    }

    private static function initSchema(string $schemaFile): void
    {
        if (self::$initialized) {
            return;
        }

        if (!file_exists($schemaFile)) {
            throw new RuntimeException("Schema file not found: $schemaFile");
        }

        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new RuntimeException("Failed to read schema file: $schemaFile");
        }
        Logger::info("Node server database initialized.");
        self::$db->exec($sql);
        self::$initialized = true;
    }

    public static function get(): SQLite3
    {
        if (self::$db === null) {
            throw new RuntimeException("DB::init() must be called before DB::get()");
        }
        return self::$db;
    }

    public static function exec(string $sql): bool
    {
        return self::get()->exec($sql);
    }

    public static function prepare(string $sql): SQLite3Stmt
    {
        return self::get()->prepare($sql);
    }
}
