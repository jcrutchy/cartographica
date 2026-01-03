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
    $player_id=Certificate::random_id(16);
    $email_token_id=Certificate::random_id(16);
    $extra=["email"=>$email,"player_id"=>$player_id,"email_token_id"=>$email_token_id];
    $expiry=600; # 10 minutes
    $issued=Certificate::issue($config,$extra,$expiry,"email_token");

    $this->assertTrue($issued["valid"],"valid issued email token",[$issued]);

    unset($issued["valid"]);
    $email_token_json=json_encode($issued);

    $url=$config->get("web_root")."/services/identity/index.php?action=redeem";
    $response_raw=$this->post($url,["email_token"=>$email_token_json]);

    $response=json_decode($response_raw,true);

    $this->assertNotNull($response,"valid json response",$response_raw);
    $this->assertIsArray($response,"decoded json response is array",$response_raw);

    $this->assertStatusOK($response,"",$response);

    $this->assertArrayHasKey("session_token",$response,"session_token field found",$response);
    $this->assertNotEmpty($response["session_token"],"session_token is not empty",$response);

    $token=json_decode($response["session_token"],true);
    
    $this->assertNotNull($token,"session_token is valid json",$response["session_token"]);
    $this->assertArrayHasKey("payload",$token,"session_token contains payload",$token);
    $this->assertArrayHasKey("signature",$token,"session_token contains signature",$token);

    $this->assertEquals("session_token",$token["payload"]["type"] ?? null,"session_token payload type is session_token",$token);
    $this->assertEquals($email,$token["payload"]["email"] ?? null,"session_token payload email matches issued email",$token);

    $verified=Certificate::verify($config,$response["session_token"]);
    $this->assertTrue($verified["valid"],"session_token signature successfully verified",$verified);
  }
}
