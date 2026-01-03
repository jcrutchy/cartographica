<?php

class MessageRouter {
    public function route(WebSocketClient $client, array $msg) {
        switch ($msg["type"]) {
            case "handshake":
                return HandshakeHandler::handle($client, $msg);
            case "move":
                return MovementHandler::handle($client, $msg);
            case "chat":
                return ChatHandler::handle($client, $msg);
            // ...
        }
    }
}
