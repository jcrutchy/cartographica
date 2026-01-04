<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\services\identity\Config;

class RedeemNegativeTest extends TestCase
{
  protected function test(): void
  {
    $config=new Config();

    $invalid_token="this-is-not-a-valid-token";

    $url=$config->get("web_root")."/services/identity/index.php?action=redeem";
    $response_raw=$this->post($url,["email_token"=>$invalid_token]);

    // Parse JSON and assert it's valid
    $response=$this->assertJson($response_raw);

    // Must be ok:false
    $this->assertStatusError($response);

    // Must contain an error message
    $this->assertArrayHasKey("error",$response);
    $this->assertNotEmpty($response["error"]);
  }
}
