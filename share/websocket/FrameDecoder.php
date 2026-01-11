<?php

namespace cartographica\share\websocket;

class FrameDecoder {
    public static function decode(string $data): ?Frame {
        if (strlen($data) < 2) return null;

        $byte1 = ord($data[0]);
        $byte2 = ord($data[1]);

        $fin = (bool)($byte1 & 0x80);
        $opcode = $byte1 & 0x0F;

        $masked = ($byte2 & 0x80) !== 0;
        $len = $byte2 & 0x7F;

        $offset = 2;

        if ($len === 126) {
            $len = unpack('n', substr($data, $offset, 2))[1];
            $offset += 2;
        } elseif ($len === 127) {
            $len = unpack('J', substr($data, $offset, 8))[1];
            $offset += 8;
        }

        $mask = '';
        if ($masked) {
            $mask = substr($data, $offset, 4);
            $offset += 4;
        }

        $payload = substr($data, $offset, $len);

        if ($masked) {
            $payload = Mask::apply($payload, $mask);
        }

        return new Frame($fin, $opcode, $payload);
    }
}
