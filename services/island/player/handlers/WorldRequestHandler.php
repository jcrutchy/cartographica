<?php

namespace cartographica\services\island\player\handlers;

use cartographica\services\island\world\state\IslandState;
use cartographica\services\island\world\state\PlayerState;
use cartographica\services\island\player\server\PlayerWebSocketClient;
use cartographica\services\island\core\protocol\Messages;

class WorldRequestHandler
{
    public function handle(PlayerWebSocketClient $client, array $msg, $state)
    {
        if ($msg['type'] !== Messages::REQUEST_WORLD) return;

        $client->send([
            'type' => Messages::WORLD,
            'tilemap' => $state->tilemap->export(),
            'players' => array_map(fn($p) => $p->export(), $state->players)
        ]);
        echo "Sending WORLD to {$client->player->id}\n";
    }
}
