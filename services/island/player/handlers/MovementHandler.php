<?php

namespace cartographica\services\island\player\handlers;

use cartographica\services\island\world\state\IslandState;
use cartographica\services\island\world\state\PlayerState;
use cartographica\services\island\player\server\PlayerWebSocketClient;
use cartographica\services\island\core\protocol\Messages;

class MovementHandler {
    public function handle(PlayerWebSocketClient $client, array $msg, IslandState $state) {
        // TODO: implement movement
    }
}
