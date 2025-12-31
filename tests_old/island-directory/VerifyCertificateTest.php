<?php

namespace cartographica\tests\islanddirectory;

use cartographica\tests\TestCase;
use cartographica\share\Request;
use cartographica\services\islanddirectory\Certificate;
use cartographica\services\islanddirectory\Config;
use cartographica\services\islanddirectory\controllers\VerifyCertificate;

class VerifyCertificateTest extends TestCase
{
    protected function test(): void
    {
        $cert = Certificate::issue(
            "FAKE_PUBLIC_KEY",
            "Test Island",
            "owner@example.com"
        );

        $req = new Request([
            "action" => "verify_certificate",
            "data" => [
                "certificate" => $cert["certificate"]
            ]
        ]);

        ob_start();
        $controller = new VerifyCertificate($req);
        $controller->handle();
        $output = ob_get_clean();

        $this->assertTrue(
            str_contains($output, '"ok":true'),
            "VerifyCertificate should return ok:true"
        );

        $this->assertTrue(
            str_contains($output, 'Test Island'),
            "VerifyCertificate should return payload"
        );
    }
}
