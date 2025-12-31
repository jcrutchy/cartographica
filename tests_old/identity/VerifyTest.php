<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\Crypto;
use cartographica\services\identity\Config;

class VerifyTest extends TestCase
{
    protected function test(): void
    {
        // Build a valid device token payload
        $payload = [
            "email"      => "test@example.com",
            "issued_at"  => time(),
            "expires_at" => time() + 300
        ];

        // Sign it with the identity private key
        $privateKey = file_get_contents(Config::privateKey());
        $deviceToken = Crypto::sign($payload, $privateKey);

        // URL for the verify action
        $url = "http://localhost/cartographica/services/identity/index.php?action=verify";

        // Make a real POST request to the server
        $response = $this->post($url, [
            "token" => $deviceToken
        ]);

        // Assertions
        $this->assertTrue(
            str_contains($response, '"ok":true'),
            "Verify should return ok:true"
        );

        $this->assertTrue(
            str_contains($response, '"email":"test@example.com"'),
            "Verify should return the correct email"
        );
    }
}
