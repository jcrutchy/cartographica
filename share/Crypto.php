<?php

/*

Crypto.php
==========

Purpose:
Signing and verifying payloads is used everywhere.
You want consistent algorithms and error handling.

Responsibilities:
Crypto::sign($payload,$privateKey)
Crypto::verify($payload,$signature,$publicKey)
Crypto::hash($data)
Crypto::randomId()

*/

namespace cartographica\share;

class Crypto {
    public static function sign(array $payload, string $privateKey): string {
        $json=json_encode($payload);
        openssl_sign($json,$signature,$privateKey,OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    public static function verify(array $payload, string $signature, string $publicKey): bool {
        $json = json_encode($payload);
        return openssl_verify($json, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    public static function randomId(int $bytes = 32): string {
        return bin2hex(random_bytes($bytes));
    }
}
