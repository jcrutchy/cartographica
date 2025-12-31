<?php

namespace cartographica\tests\island;

use cartographica\tests\TestCase;
use cartographica\services\island\Config;
use cartographica\services\island\controllers\GetIslandInfo;

class GetIslandInfoTest extends TestCase
{
    protected function test(): void
    {
        ob_start();
        $controller = new GetIslandInfo();
        $controller->handle();
        $output = ob_get_clean();

        $this->assertTrue(
            str_contains($output, '"ok":true'),
            "GetIslandInfo should return ok:true"
        );

        $this->assertTrue(
            str_contains($output, 'name'),
            "GetIslandInfo should return island metadata"
        );
    }
}
