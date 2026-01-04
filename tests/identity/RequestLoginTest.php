<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\services\identity\Config;

class RequestLoginTest extends TestCase
{
  protected function test(): void
  {
    $url="http://localhost/cartographica/services/identity/index.php?action=request_login";
    $response_raw=$this->post($url,["email"=>Config::get("admin_email")]);

    // Parse JSON
    $response=$this->assertJson($response_raw);

    // Should return ok:true
    $this->assertStatusOK($response);

    // Should contain status: "sent"
    $this->assertArrayHasKey("status",$response);
    $this->assertEquals("sent",$response["status"]);
  }
}
