<?php

namespace cartographica\tests\islanddirectory;

use cartographica\tests\TestCase;
use cartographica\share\Request;
use cartographica\services\islanddirectory\controllers\RegisterIsland;

class RegisterIslandTest extends TestCase
{
    protected function test(): void
    {
        $req = new Request([
            "action" => "register_island",
            "data" => [
                "name"       => "Test Island",
                "owner"      => "owner@example.com",
                "public_key" => "FAKE_PUBLIC_KEY"
            ]
        ]);

        ob_start();
        $controller = new RegisterIsland($req);
        $controller->handle();
        $output = ob_get_clean();

        $this->assertTrue(
            str_contains($output, '"ok":true'),
            "RegisterIsland should return ok:true"
        );

        $this->assertTrue(
            str_contains($output, 'certificate'),
            "RegisterIsland should return a certificate"
        );
    }
}
