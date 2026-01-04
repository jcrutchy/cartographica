<?php

namespace cartographica\services\island\player\handlers;

use cartographica\services\island\world\state\IslandState;
use cartographica\services\island\world\state\PlayerState;
use cartographica\services\island\player\server\PlayerWebSocketClient;
use cartographica\services\island\core\protocol\Messages;

class HandshakeHandler {
    public function handle(PlayerWebSocketClient $client, array $msg, IslandState $state) {

        if ($msg['type'] === Messages::HELLO) {
            $client->send(['type' => Messages::HELLO]);
            echo "Sent HELLO to client\n";
            return;
        }

        if ($msg['type'] === Messages::AUTH) {
            // TODO: verify signature
            $player = new PlayerState($msg['payload']);
            $client->player = $player;
            $state->players[$client->player->id] = $player;

            $client->send([
                'type' => Messages::AUTH_OK,
                'player' => $player->export()
            ]);
            echo "AUTH received from {$player->id}\n";
            return;
        }
    }
}
