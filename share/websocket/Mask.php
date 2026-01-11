<?php

namespace cartographica\share\websocket;

class Mask {
    public static function apply(string $data, string $mask): string {
        $out = '';
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $out .= $data[$i] ^ $mask[$i % 4];
        }
        return $out;
    }
}
