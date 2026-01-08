<?php

namespace Cartographica\Supervisor;

use Cartographica\Supervisor\Config;
use Cartographica\Supervisor\IslandDefinition;
use Cartographica\Supervisor\IslandProcess;
use Cartographica\Supervisor\Logger;

class IslandRegistry
{
    /** @var array<string, IslandProcess> */
    private array $islands = [];

    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public static function fromConfig(Config $config, Logger $logger): self
    {
        $reg = new self($logger);

        foreach ($config->getIslands() as $defArr) {

            $def = IslandDefinition::fromArray($defArr);

            // Extract fields needed by IslandProcess
            $id  = $def->id;
            $cmd = $def->command;      // e.g. "{php} {repo}/island/island.php {id}"
            $cwd = $def->cwd;          // or $def->data_path depending on your config

            $reg->islands[$id] = new IslandProcess(
                $id,
                $cmd,
                $cwd,
                $logger
            );
        }

        return $reg;
    }

    /** @return IslandProcess[] */
    public function all(): array
    {
        return array_values($this->islands);
    }

    public function get(string $id): ?IslandProcess
    {
        return $this->islands[$id] ?? null;
    }
}
