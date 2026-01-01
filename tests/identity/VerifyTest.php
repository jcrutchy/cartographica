<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\Crypto;
use cartographica\share\SharedConfig;
use cartographica\services\identity\Config;

class VerifyTest extends TestCase
{
    protected function test(): void
    {
        // Build a valid device token payload
        $payload = [
            "email"      => SharedConfig::get("admin_email"),
            "issued_at"  => time(),
            "expires_at" => time() + 300
        ];

        // Sign it with the identity private key
        $privateKey = file_get_contents(Config::privateKey());
        $signature = Crypto::sign($payload, $privateKey);

        // URL for the verify action
        $url = "http://localhost/cartographica/services/identity/index.php?action=verify";

        // Make a real POST request to the server
        $token = json_encode([
            "payload"   => $payload,
            "signature" => $signature
        ]);
        $response = $this->post($url, [
            "device_token" => $token
        ]);

        // Assertions
        $this->assertTrue(
            str_contains($response, '"valid":true'),
            "Verify should return valid:true",
            [$response]
        );

        $this->assertTrue(
            str_contains($response, '"email":"'.$payload["email"].'"'),
            "Verify should return the correct email",
            [$response]
        );
    }
}
