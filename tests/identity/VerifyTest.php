<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\Certificate;
use cartographica\services\identity\Config;

class VerifyTest extends TestCase
{
  protected function test(): void
  {
    $config=new Config();
    $email=$config->get("admin_email");

    // Issue a valid session token
    $extra = [
      "email"=>$email,
      "player_id"=>Certificate::random_id(16),
      "email_token_id"=>Certificate::random_id(16)
    ];
    $expiry=86400*30; # 30 days
    $issued=Certificate::issue($config,$extra,$expiry,"session_token");
    $this->assertTrue($issued["valid"],"session token should be valid",$issued);

    unset($issued["valid"]);
    $session_token_json=json_encode($issued);

    // Call verify endpoint
    $url=$config->get("web_root")."/services/identity/index.php?action=verify";
    $response_raw=$this->post($url,["session_token"=>$session_token_json]);

    // Parse JSON
    $response=$this->assertJson($response_raw);

    // Verify endpoint should return ok:true
    $this->assertStatusOK($response);

    // Should contain "valid": true
    $this->assertArrayHasKey("valid",$response);
    $this->assertTrue($response["valid"]);

    // Should contain payload
    $this->assertArrayHasKey("payload",$response);

    // Should contain correct email inside payload
    $payload=$response["payload"];
    $this->assertArrayHasKey("email",$payload);
    $this->assertEquals($email,$payload["email"]);
  }
}
