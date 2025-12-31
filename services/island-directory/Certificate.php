<?php

namespace cartographica\services\islanddirectory;

use cartographica\share\Crypto;

class Certificate
{
    public static function issue(string $publicKey, string $name, string $ownerEmail): array
    {
        $payload = [
            "public_key" => $publicKey,
            "name"       => $name,
            "owner"      => $ownerEmail,
            "issued_at"  => time(),
            "expires_at" => time() + (86400 * 30) // 30 days
        ];

        $privateKey = file_get_contents(Config::privateKey());
        $certificate = Crypto::sign($payload, $privateKey);

        return [
            "payload"     => $payload,
            "certificate" => $certificate
        ];
    }

    public static function verify(string $certificate): array
    {
        $publicKey = file_get_contents(Config::publicKey());

        $payload = json_decode(base64_decode($certificate), true);

        if (!is_array($payload)) {
            return ["valid" => false, "error" => "Invalid certificate format"];
        }

        if (!Crypto::verify($payload, $certificate, $publicKey)) {
            return ["valid" => false, "error" => "Invalid signature"];
        }

        if ($payload["expires_at"] < time()) {
            return ["valid" => false, "error" => "Certificate expired"];
        }

        return ["valid" => true, "payload" => $payload];
    }
}
