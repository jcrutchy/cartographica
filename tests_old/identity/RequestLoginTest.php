<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;

class RequestLoginTest extends TestCase
{
    protected function test(): void
    {
        $url = "http://localhost/cartographica/services/identity/index.php?action=request_login";

        $response = $this->post($url, [
            "email" => "test@example.com"
        ]);

        $this->assertTrue(
            str_contains($response, '"ok":true'),
            "RequestLogin should return ok:true"
        );

        $this->assertTrue(
            str_contains($response, 'login_token'),
            "RequestLogin should return a login_token"
        );
    }
}
