<?php

namespace cartographica\services\island\core\websocket;

class Frame {
    public bool $fin;
    public int $opcode;
    public string $payload;

    public function __construct(bool $fin, int $opcode, string $payload) {
        $this->fin = $fin;
        $this->opcode = $opcode;
        $this->payload = $payload;
    }
}
