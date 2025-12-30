<?php

function verify_identity_token(array $payload, string $signature): bool {
    $publicKey = openssl_pkey_get_public(file_get_contents(CA_PUBLIC_KEY));
    $json = json_encode($payload);
    return openssl_verify($json, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256) === 1;
}
