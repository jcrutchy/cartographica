<?php

namespace cartographica\share;

function Http_post($url, $payload) {
    return [
        "ok" => true,
        "data" => [
            "payload" => [
                "email" => "player@example.com"
            ]
        ]
    ];
}

namespace cartographica\tests\island;

use cartographica\tests\TestCase;
use cartographica\share\Request;
use cartographica\services\island\controllers\Handshake;

class HandshakeTest extends TestCase
{
    protected function test(): void
    {
        $req = new Request([
            "action" => "handshake",
            "data" => [
                "device_token" => "FAKE_TOKEN"
            ]
        ]);

        ob_start();
        $controller = new Handshake($req);
        $controller->handle();
        $output = ob_get_clean();

        $this->assertTrue(
            str_contains($output, '"ok":true'),
            "Handshake should return ok:true"
        );

        $this->assertTrue(
            str_contains($output, 'player@example.com'),
            "Handshake should return verified device payload"
        );
    }
}
