<?php

namespace cartographica\services\island;

use cartographica\share\SharedConfig;
use cartographica\share\Env;

class Config extends SharedConfig
{
    public static string $service="island";

    public string $islandId;

    public function __construct(string $islandId) {
        $this->islandId = $islandId;
    }
  
    public function islandConfigFilename(): string
    {
        return Env::serviceData(static::$service)."/".$this->islandId."/island_config.json";
    }

    public function loadIslandConfig(): array
    {
        if (isset($this->cache["island_id"])==true)
        {
          return $this->cache;
        }
        $path = $this->islandConfigFilename();
        if (!file_exists($path))
        {
          throw new \RuntimeException("Island config not found: $path");
        }
        $json=file_get_contents($path);
        $island=json_decode($json,true);
        $this->cache=$island;
        return $this->cache;
    }

}
