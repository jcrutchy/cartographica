<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\Certificate;
use cartographica\services\identity\Config;

class RedeemNegativeTest extends TestCase
{
  protected function test(): void
  {
    $config=new Config();

    $invalid_token="this-is-not-a-valid-token";

    $url=$config->get("web_root")."/services/identity/index.php?action=redeem";
    $response_raw=$this->post($url,["email_token"=>$invalid_token]);

    $response=json_decode($response_raw,true);

    // Basic JSON validity
    #$this->assertNotNull($response,"Redeem returned invalid JSON",$response_raw);
    #$this->assertIsArray($response,"Redeem response should be an array",$response_raw);

    // Should NOT be ok:true
    #$this->assertArrayHasKey("ok",$response,"Response missing 'ok' field",$response);
    $this->assertFalse($response["ok"],"Redeem should fail for invalid token",$response);

    // Should contain an error message
    $this->assertArrayHasKey("error",$response,"Redeem error response missing 'error' field",$response);
    $this->assertNotEmpty($response["error"],"Redeem error message should not be empty",$response);
  }
}
