<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\SharedConfig;

class RequestLoginTest extends TestCase
{
    protected function test(): void
    {
        $url = "http://localhost/cartographica/services/identity/index.php?action=request_login";

        $response = $this->post($url, [
            "email" => SharedConfig::get("admin_email")
        ]);

        $this->assertTrue(
            str_contains($response, '"ok":true'),
            "RequestLogin should return ok:true",
            [$response]
        );

        $this->assertTrue(
            str_contains($response, '"status":"sent"'),
            "RequestLogin should return status:sent",
            [$response]
        );
    }
}
