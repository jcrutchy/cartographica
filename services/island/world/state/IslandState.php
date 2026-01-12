<?php

namespace cartographica\services\island\world\state;

use cartographica\services\island\world\tilemap\Tilemap;
use cartographica\services\island\Config;

class IslandState {
    public Tilemap $tilemap;
    public array $players = [];

    public function __construct(Config $config) {
        $this->tilemap = new Tilemap($config);
    }
}
