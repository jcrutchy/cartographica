<?php

namespace cartographica\services\island\player\server;

use cartographica\services\island\core\websocket\Frame;
use cartographica\services\island\core\websocket\FrameEncoder;
use cartographica\services\island\core\websocket\FrameDecoder;
use cartographica\services\island\core\websocket\Opcode;

class PlayerWebSocketClient {
    private $socket;
    private $server;
    public ?object $player = null;

    public function __construct($socket, PlayerWebSocketServer $server) {
        $this->socket = $socket;
        $this->server = $server;
    }

    public function send(array $msg): void {
        $json = json_encode($msg);
        $frame = new Frame(true, Opcode::TEXT, $json);
        fwrite($this->socket, FrameEncoder::encode($frame));
    }

    public function read(): ?array {
        $raw = @fread($this->socket, 2048);
        if (!$raw) return null;

        $frame = FrameDecoder::decode($raw);
        if (!$frame) return null;

        if ($frame->opcode === Opcode::TEXT) {
            return json_decode($frame->payload, true);
        }

        return null;
    }
}
