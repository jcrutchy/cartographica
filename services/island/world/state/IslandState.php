<?php

namespace cartographica\services\island\world\state;

use cartographica\services\island\world\tilemap\Tilemap;

class IslandState {
    public Tilemap $tilemap;
    public array $players = [];

    public function __construct(string $seed = 'starter_01') {
        $this->tilemap = new Tilemap(10, 10, $seed);
    }
}
