<?php

class Logger
{
    private static ?string $logFile = null;

    public static function init(?string $filePath = null): void
    {
        self::$logFile = $filePath;
    }

    public static function info(string $msg): void
    {
        self::write("INFO", $msg);
    }

    public static function warn(string $msg): void
    {
        self::write("WARN", $msg);
    }

    public static function error(string $msg): void
    {
        self::write("ERROR", $msg);
    }

    private static function write(string $level, string $msg): void
    {
        $colors = [
            "INFO"  => "\033[32m", // green
            "WARN"  => "\033[33m", // yellow
            "ERROR" => "\033[31m", // red
        ];
    
        $reset = "\033[0m";
        $color = $colors[$level] ?? "";
    
        $line = "[" . date("H:i:s") . "] $color$level$reset: $msg\n";
    
        echo $line;
    
        if (self::$logFile) {
            file_put_contents(self::$logFile, strip_tags($line), FILE_APPEND);
        }
    }

}
