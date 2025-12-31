<?php

/*

Pki.php
=======

Purpose:
Both island servers and the island‑directory need to verify certificates.
You want a single source of truth for certificate rules.
This becomes the heart of your trust model.

Features:
- Replay protection (later)

Responsibilities:
Pki::validateCertificateStructure($cert)
Pki::verifyCertificateSignature($cert,$signature,$publicKey)
Pki::checkCertificateExpiry($cert)
Pki::verifyCertificateIssuer($cert,$expectedIssuer)

*/

namespace cartographica\share;

class Pki {
    public static function validateStructure(array $cert): bool {
        $required = ["node_id", "network_id", "public_key", "issued_at", "expires_at"];
        foreach ($required as $key) {
            if (!isset($cert[$key])) return false;
        }
        return true;
    }

    public static function verifySignature(array $cert, string $signature, string $publicKey): bool {
        return Crypto::verify($cert, $signature, $publicKey);
    }

    public static function checkExpiry(array $cert): bool {
        return $cert["expires_at"] >= time();
    }
}
