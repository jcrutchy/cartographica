<?php

/*

Logger.php
==========

Purpose:
Every service needs logging.

Features:
- Consistent formatting and optional terminal output
- Automatic timestamps
- Automatic file rotation (later)

Responsibilities:
Logger::init($file, $toConsole = true)
Logger::info(), Logger::warn(), Logger::error()

*/

namespace cartographica\share;

class Logger {
    private static string $file;
    private static bool $console = true;

    public static function init(string $file, bool $console = true): void {
        self::$file = $file;
        self::$console = $console;
    }

    public static function info(string $msg): void {
        self::write("INFO", $msg);
    }

    public static function error(string $msg): void {
        self::write("ERROR", $msg);
    }

    private static function write(string $level, string $msg): void {
        $line = "[" . date("Y-m-d H:i:s") . "] $level: $msg\n";
        file_put_contents(self::$file, $line, FILE_APPEND);

        if (self::$console) {
            echo $line;
        }
    }
}
