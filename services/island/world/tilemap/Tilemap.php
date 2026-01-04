<?php

namespace cartographica\services\island\world\tilemap;

class Tilemap {
    public array $tiles = [];

    public function __construct(int $w, int $h) {
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $this->tiles[$y][$x] = [
                    'terrain' => 'grass',
                    'x' => $x,
                    'y' => $y
                ];
            }
        }
    }

    public function export(): array {
        return $this->tiles;
    }
}
