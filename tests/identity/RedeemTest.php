<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\Crypto;
use cartographica\services\identity\Config;

class RedeemTest extends TestCase
{
    protected function test(): void
    {
        // Build a valid login token
        $payload = [
            "email"      => "test@example.com",
            "issued_at"  => time(),
            "expires_at" => time() + 300
        ];

        $privateKey = file_get_contents(Config::privateKey());
        $signature = Crypto::sign($payload, $privateKey);

        // URL of the running identity service
        $url = "http://localhost/cartographica/services/identity/index.php?action=redeem";

        $token = json_encode([
            "payload"   => $payload,
            "signature" => $signature
        ]);

        // Make a real POST request to the server
        $response = $this->post($url, [
            "token" => $token
        ]);

        // Assertions
        $this->assertTrue(
            str_contains($response, '"ok":true'),
            "Redeem should return ok:true",
            [$response]
        );

        $this->assertTrue(
            str_contains($response, 'device_token'),
            "Redeem should return a device_token",
            [$response]
        );
    }
}
