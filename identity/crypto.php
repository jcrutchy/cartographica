<?php
function sign_payload(array $payload): string {
    $privateKey = openssl_pkey_get_private(file_get_contents(CA_PRIVATE_KEY));
    $json = json_encode($payload);
    openssl_sign($json, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);
}

function verify_payload(array $payload, string $signature): bool {
    $publicKey = openssl_pkey_get_public(file_get_contents(CA_PUBLIC_KEY));
    $json = json_encode($payload);
    return openssl_verify($json, base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256) === 1;
}
