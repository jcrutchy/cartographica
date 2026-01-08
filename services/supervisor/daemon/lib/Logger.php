<?php

/*
Logger.php
- Append to log file / stdout
- Simple levels: info/warn/error/debug
*/

namespace Cartographica\Supervisor;

class Logger
{
    private string $path;

    public function __construct(string $path) {
        $this->path = $path;
    }

    public function info(string $msg): void { $this->write('INFO', $msg); }
    public function warn(string $msg): void { $this->write('WARN', $msg); }
    public function error(string $msg): void { $this->write('ERROR', $msg); }

    private function write(string $level, string $msg): void {
        $line = sprintf("[%s] [%s] %s\n", date('c'), $level, $msg);
        file_put_contents($this->path, $line, FILE_APPEND);
        echo $line;
    }
}
