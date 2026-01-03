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
    $player_id=Certificate::random_id(16);
    $email_token_id=Certificate::random_id(16);
    $extra=["email"=>$email,"player_id"=>$player_id,"email_token_id"=>$email_token_id];
    $expiry=86400*30; # 30 days
    $response=Certificate::issue($config,$extra,$expiry,"session_token");
    if ($response["valid"]==true)
    {
      unset($response["valid"]);
      $session_token_json=json_encode($response);
      $url=$config->get("web_root")."/services/identity/index.php?action=verify";
      $response=$this->post($url,["session_token"=>$session_token_json]);
    }
    $this->assertTrue(
      str_contains($response, '"valid":true'),
      "Verify should return valid:true",
      [$response]
    );
    $this->assertTrue(
      str_contains($response, '"email":"'.$email.'"'),
      "Verify should return the correct email",
      [$response]
    );
  }
}
