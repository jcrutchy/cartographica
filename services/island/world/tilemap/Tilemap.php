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

    // ---------------------------------------------------------
    // 2D Value Noise (coherent)
    // ---------------------------------------------------------
    private function valueNoise2D(float $x, float $y, string $seed): float
    {
        $x0 = floor($x);
        $x1 = $x0 + 1;
        $y0 = floor($y);
        $y1 = $y0 + 1;

        $v00 = $this->hash2D($x0, $y0, $seed);
        $v10 = $this->hash2D($x1, $y0, $seed);
        $v01 = $this->hash2D($x0, $y1, $seed);
        $v11 = $this->hash2D($x1, $y1, $seed);

        $tx = $x - $x0;
        $ty = $y - $y0;

        $tx = $tx * $tx * (3 - 2 * $tx);
        $ty = $ty * $ty * (3 - 2 * $ty);

        $nx0 = $v00 * (1 - $tx) + $v10 * $tx;
        $nx1 = $v01 * (1 - $tx) + $v11 * $tx;

        return $nx0 * (1 - $ty) + $nx1 * $ty;
    }

    private function hash2D(int $x, int $y, string $seed): float
    {
        $hash = crc32($seed . "_{$x}_{$y}");
        return ($hash % 10000) / 10000.0;
    }

    // ---------------------------------------------------------
    // Elevation + Moisture
    // ---------------------------------------------------------
    private function elevation(float $x, float $y, string $seed, int $width, int $height): float
    {
        $nx = ($x / $width) * 2 - 1;
        $ny = ($y / $height) * 2 - 1;

        $distance = sqrt($nx * $nx + $ny * $ny);
        $falloff = 1 - pow($distance, 2.5);
        $falloff = max(0, $falloff);

        $macro = $this->valueNoise2D($x * 0.01, $y * 0.01, $seed);
        $micro = $this->valueNoise2D($x * 0.05, $y * 0.05, $seed . "_detail");

        return ($macro * 0.7 + $micro * 0.3) * $falloff;
    }

    private function moisture(float $x, float $y, string $seed): float
    {
        return $this->valueNoise2D($x * 0.02, $y * 0.02, $seed . "_moist");
    }

    // ---------------------------------------------------------
    // Biome Classification
    // ---------------------------------------------------------
    private function classify(float $e, float $m, array $palette): string
    {
        if ($e < 0.15) return "water";
        if ($e < 0.22) return "shore";
        if ($e < 0.45) return ($m > 0.5 ? "swamp" : "plains");
        if ($e < 0.65) return "grassland";
        if ($e < 0.8)  return "desert";
        return "tundra";
    }

    // ---------------------------------------------------------
    // Coastline Helpers
    // ---------------------------------------------------------
    private function isLand(string $biome): bool {
        return !in_array($biome, ["water", "shore"]);
    }

    private function biomeAt(array $map, int $x, int $y, int $w, int $h): string {
        if ($x < 0 || $y < 0 || $x >= $w || $y >= $h) return "water";
        return $map[$y][$x]["biome"];
    }

    private function isCoastTile(array $map, int $x, int $y, int $w, int $h): bool
    {
        $biome = $map[$y][$x]["biome"];
        if ($biome === "water") return false;

        // True coastline = land touching water
        return (
            $this->biomeAt($map, $x, $y - 1, $w, $h) === "water" ||
            $this->biomeAt($map, $x, $y + 1, $w, $h) === "water" ||
            $this->biomeAt($map, $x - 1, $y, $w, $h) === "water" ||
            $this->biomeAt($map, $x + 1, $y, $w, $h) === "water"
        );
    }

    // Quarter selection (simple version)
    private function pickQuarter(array $map, int $x, int $y, string $corner, int $w, int $h): int
    {
        switch ($corner) {
            case "tl":
                $n  = $this->isLand($this->biomeAt($map, $x,     $y-1, $w, $h));
                $w_ = $this->isLand($this->biomeAt($map, $x-1,   $y,   $w, $h));
                $nw = $this->isLand($this->biomeAt($map, $x-1,   $y-1, $w, $h));
                break;

            case "tr":
                $n  = $this->isLand($this->biomeAt($map, $x,     $y-1, $w, $h));
                $e  = $this->isLand($this->biomeAt($map, $x+1,   $y,   $w, $h));
                $ne = $this->isLand($this->biomeAt($map, $x+1,   $y-1, $w, $h));
                break;

            case "bl":
                $s  = $this->isLand($this->biomeAt($map, $x,     $y+1, $w, $h));
                $w_ = $this->isLand($this->biomeAt($map, $x-1,   $y,   $w, $h));
                $sw = $this->isLand($this->biomeAt($map, $x-1,   $y+1, $w, $h));
                break;

            case "br":
                $s  = $this->isLand($this->biomeAt($map, $x,     $y+1, $w, $h));
                $e  = $this->isLand($this->biomeAt($map, $x+1,   $y,   $w, $h));
                $se = $this->isLand($this->biomeAt($map, $x+1,   $y+1, $w, $h));
                break;
        }

        $landCount = 0;
        foreach (get_defined_vars() as $v) {
            if ($v === true) $landCount++;
        }

        return match ($landCount) {
            0 => 0,   // pure water quarter
            1 => 1,   // edge
            2 => 3,   // corner
            3 => 7,   // full land quarter
        };
    }

    // ---------------------------------------------------------
    // Main Tilemap Generation
    // ---------------------------------------------------------
    private function generateTilemap(Config $config): array
    {
        $cfg = $config->cache;
        $width = $cfg["w"];
        $height = $cfg["h"];
        $seed = $cfg["tilemap_seed"];

        $asset_path = Env::serviceData("island") . "/" . $config->islandId . "/assets/";
        $tilesetConfigPath = $asset_path . "default_terrain.json";
        $tileset = json_decode(file_get_contents($tilesetConfigPath), true);

        $palette = $tileset["map_palette"];
        $coastCfg = $tileset["coastlines"];

        $map = [];

        // First pass: biome + elevation
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {

                $e = $this->elevation($x, $y, $seed, $width, $height);
                $m = $this->moisture($x, $y, $seed);

                $biome = $this->classify($e, $m, $palette);

                $map[$y][$x] = [
                    "biome" => $biome,
                    "tile"  => $palette[$biome],
                    "coast" => null
                ];
            }
        }

        // Second pass: coastline generation
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {

                if (!$this->isCoastTile($map, $x, $y, $width, $height)) {
                    $map[$y][$x]["coast"] = null;
                    continue;
                }

                $biome = $map[$y][$x]["biome"];
                $coastType = $coastCfg["biome_map"][$biome] ?? "grassland";
                $coastRow = $coastCfg[$coastType]["row"];

                $map[$y][$x]["coast"] = [
                    "row" => $coastRow,
                    "tl"  => $this->pickQuarter($map, $x, $y, "tl", $width, $height),
                    "tr"  => $this->pickQuarter($map, $x, $y, "tr", $width, $height),
                    "bl"  => $this->pickQuarter($map, $x, $y, "bl", $width, $height),
                    "br"  => $this->pickQuarter($map, $x, $y, "br", $width, $height)
                ];
            }
        }

        return $map;
    }
}
