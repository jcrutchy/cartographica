<?php

namespace cartographica\share\websocket;

use cartographica\share\websocket\Frame;
use cartographica\share\websocket\FrameEncoder;
use cartographica\share\websocket\FrameDecoder;
use cartographica\share\websocket\Opcode;

class WebSocketClient {
    protected $socket;
    protected $server;

    public function __construct($socket, WebSocketServer $server) {
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

        if ($frame->opcode === Opcode::TEXT)
        {
            echo $frame->payload.PHP_EOL;
            return json_decode($frame->payload, true);
        }

        return null;
    }
}
