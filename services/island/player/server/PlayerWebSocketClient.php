<?php

namespace cartographica\services\island\player\server;

use cartographica\share\websocket\WebSocketClient;
use cartographica\share\websocket\Frame;
use cartographica\share\websocket\FrameEncoder;
use cartographica\share\websocket\FrameDecoder;
use cartographica\share\websocket\Opcode;

class PlayerWebSocketClient extends WebSocketClient
{
    public ?object $player = null;

    public function __construct($socket, $server)
    {
        parent::__construct($socket, $server);
    }

    public function send(array $msg): void {
        $json = json_encode($msg);
        $frame = new Frame(true, Opcode::TEXT, $json);
        fwrite($this->socket, FrameEncoder::encode($frame));
    }
}
