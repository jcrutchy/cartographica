<?php

namespace cartographica\services\island\world\tilemap;

class Tilemap {
    public array $tiles = [];

    public static array $tileLegend = [
        0 => 'Water',
        1 => 'Shore',
        2 => 'Grassland',
        3 => 'Forest',
        4 => 'Hills',
        5 => 'Mountain',
        6 => 'Village'
    ];

    public function __construct(int $w, int $h, string $seed) {
        $this->tiles = $this->generateTilemap($w, $h, $seed);
    }

    public function export(): array {
        return $this->tiles;
    }

    private function noise(int $x, int $y, string $seed): float {
        $hash = crc32($seed . "_{$x}_{$y}");
        mt_srand($hash);
        return mt_rand() / mt_getrandmax(); // 0.0 to 1.0
    }

    private function generateTilemap(int $width, int $height, string $seed): array {
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
    
                // Biome mapping
                if ($elevation < 0.2) {
                    $tile = 0; // Water
                } elseif ($elevation < 0.3) {
                    $tile = 1; // Shore
                } elseif ($elevation < 0.45) {
                    $tile = 2; // Grassland
                } elseif ($elevation < 0.6) {
                    $tile = 3; // Forest
                } elseif ($elevation < 0.75) {
                    $tile = 4; // Hills
                } elseif ($elevation < 0.9) {
                    $tile = 5; // Mountain
                } else {
                    $tile = 6; // Village (rare)
                }
    
                $row[] = $tile;
            }
            $map[] = $row;
        }
    
        return $map;
    }
}
