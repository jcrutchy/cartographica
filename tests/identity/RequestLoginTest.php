<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\services\identity\Config;

class RequestLoginTest extends TestCase
{
  protected function test(): void
  {
    $url="http://localhost/cartographica/services/identity/index.php?action=request_login";
    $response=$this->post($url,["email"=>Config::get("admin_email")]);
    $this->assertTrue(
      str_contains($response,'"ok":true'),
      "RequestLogin should return ok:true",
      [$response]
    );
    $this->assertTrue(
      str_contains($response,'"status":"sent"'),
      "RequestLogin should return status:sent",
      [$response]
    );
  }
}
