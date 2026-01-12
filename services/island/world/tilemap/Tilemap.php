<?php

namespace cartographica\services\island\world\tilemap;

use cartographica\services\island\Config;
use cartographica\share\Env;

class Tilemap {
    public array $tiles = [];

    public function __construct(Config $config)
    {
        $this->tiles = $this->generateTilemap($config);
    }

    public function export(): array {
        return $this->tiles;
    }

    private function noise(int $x, int $y, string $seed): float {
        $hash = crc32($seed . "_{$x}_{$y}");
        mt_srand($hash);
        return mt_rand() / mt_getrandmax(); // 0.0 to 1.0
    }

    private function generateTilemap(Config $config): array
    {

        $cfg = $config->cache;
        $width = $cfg["w"];
        $height = $cfg["h"];
        $seed = $cfg["tilemap_seed"];

        $asset_path=Env::serviceData("island")."/".$config->islandId."/assets/";
        $tilesetConfigPath=$asset_path."default_terrain.json";
        $default_tileset_cfg=json_decode(file_get_contents($tilesetConfigPath), true);

        $map = [];
        echo "generating tilemap based on seed \"".$seed."\"".PHP_EOL;
        for ($y = 0; $y < $height; $y++) {
            $row = [];
            for ($x = 0; $x < $width; $x++) {
                // Normalize coordinates to -1..1
                $nx = ($x / $width) * 2 - 1;
                $ny = ($y / $height) * 2 - 1;
    
                // Radial falloff
                $distance = sqrt($nx * $nx + $ny * $ny);
                $falloff = 1.0 - $distance;
    
                // Elevation with noise
                $elevation = $this->noise($x, $y, $seed) * $falloff;
    
/*
  "map_palette": {
    "water": 0,
    "shore": 1,
    "grassland": 2,
    "desert": 3,
    "plains": 4,
    "tundra": 5,
    "snow": 5,
    "swamp": 7
  },
*/
    
                // Biome mapping
                if ($elevation < 0.2) {
                    $tile = $default_tileset_cfg["map_palette"]["water"];
                } elseif ($elevation < 0.3) {
                    $tile = $default_tileset_cfg["map_palette"]["shore"];
                } elseif ($elevation < 0.45) {
                    $tile = $default_tileset_cfg["map_palette"]["plains"];
                } elseif ($elevation < 0.6) {
                    $tile = $default_tileset_cfg["map_palette"]["grassland"];
                } elseif ($elevation < 0.75) {
                    $tile = $default_tileset_cfg["map_palette"]["desert"];
                } else {
                    $tile = $default_tileset_cfg["map_palette"]["tundra"];
                }
    
                $row[] = $tile;
            }
            $map[] = $row;
        }
    
        return $map;
    }
}
