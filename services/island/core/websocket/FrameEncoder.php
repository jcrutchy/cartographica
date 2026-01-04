<?php

namespace cartographica\services\island\core\websocket;

class FrameEncoder {
    public static function encode(Frame $frame): string {
        $finBit = $frame->fin ? 0x80 : 0x00;
        $header = chr($finBit | $frame->opcode);

        $len = strlen($frame->payload);

        if ($len <= 125) {
            $header .= chr($len);
        } elseif ($len <= 65535) {
            $header .= chr(126) . pack('n', $len);
        } else {
            $header .= chr(127) . pack('J', $len);
        }

        return $header . $frame->payload;
    }
}
