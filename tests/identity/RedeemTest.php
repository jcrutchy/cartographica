<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\Certificate;
use cartographica\services\identity\Config;

class RedeemTest extends TestCase
{
  protected function test(): void
  {
    $config=new Config();
    $email=$config->get("admin_email");

    // Issue a valid email token
    $extra = [
      "email"=> $email,
      "player_id"=>Certificate::random_id(16),
      "email_token_id"=>Certificate::random_id(16)
    ];
    $expiry=600; # 10 minutes
    $issued=Certificate::issue($config,$extra,$expiry,"email_token");
    $this->assertTrue($issued["valid"],"email token should be valid",$issued);

    unset($issued["valid"]);
    $email_token_json=json_encode($issued);

    // Redeem it
    $url=$config->get("web_root")."/services/identity/index.php?action=redeem";
    $response_raw=$this->post($url,["email_token"=>$email_token_json]);

    // Validate JSON + structure
    $response=$this->assertJson($response_raw);
    $this->assertStatusOK($response);

    // Validate session_token exists and is non-empty
    $this->assertArrayHasKey("session_token",$response);
    $this->assertNotEmpty($response["session_token"]);

    // Decode session token
    $token = $this->assertJson($response["session_token"]);

    // Validate token structure
    $this->assertArrayHasKey("payload",$token);
    $this->assertArrayHasKey("signature",$token);

    // Validate payload fields
    $payload = $token["payload"];
    $this->assertEquals("session_token",$payload["type"] ?? null);
    $this->assertEquals($email,$payload["email"] ?? null);

    // Validate signature
    $verified=Certificate::verify($config,$response["session_token"]);
    $this->assertTrue($verified["valid"],"session_token signature should verify",$verified);
  }
}
