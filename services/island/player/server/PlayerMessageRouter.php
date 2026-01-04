<?php

namespace cartographica\services\island\player\server;

use cartographica\services\island\core\protocol\Messages;
use cartographica\services\island\player\handlers\HandshakeHandler;
use cartographica\services\island\player\handlers\MovementHandler;
use cartographica\services\island\player\handlers\ChatHandler;
use cartographica\services\island\player\handlers\WorldRequestHandler;

class PlayerMessageRouter {
    private array $handlers;

    public function __construct() {
        $this->handlers = [
            Messages::HELLO => new HandshakeHandler(),
            Messages::AUTH => new HandshakeHandler(),
            Messages::REQUEST_WORLD => new WorldRequestHandler(),
            Messages::MOVE => new MovementHandler(),
            Messages::CHAT => new ChatHandler(),
        ];
    }

    public function route(PlayerWebSocketClient $client, array $msg, $state): void {
        $type = $msg['type'] ?? null;
        echo "Routing message type: $type\n";
    
        if (!$type || !isset($this->handlers[$type])) {
            echo "No handler for message type: $type\n";
            return;
        }
    
        $this->handlers[$type]->handle($client, $msg, $state);
    }

}
