<?php

/*
IslandDefinition.php
- Pure data: id, label, command, working dir, base port, etc.
*/

namespace Cartographica\Supervisor;

class IslandDefinition
{
    public string $id;
    public string $command;
    public string $cwd;
    public int $port;

    public function __construct(string $id, string $command, string $cwd, int $port) {
        $this->id = $id;
        $this->command = $command;
        $this->cwd = $cwd;
        $this->port = $port;
    }

    public static function fromArray(array $a): self {
        return new self(
            $a['island_id'],
            $a['command'],
            $a['cwd'],
            $a['port']
        );
    }
}
