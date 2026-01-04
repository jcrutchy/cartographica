<?php

namespace cartographica\services\island\core\websocket;

class Opcode {
    const CONTINUATION = 0x0;
    const TEXT = 0x1;
    const BINARY = 0x2;
    const CLOSE = 0x8;
    const PING = 0x9;
    const PONG = 0xA;
}
