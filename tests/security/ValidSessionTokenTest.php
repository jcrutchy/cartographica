<?php

namespace cartographica\tests\security;

use cartographica\tests\TestCase;
use cartographica\share\Certificate;
use cartographica\services\identity\Config as IdentityConfig;

class ValidSessionTokenTest extends TestCase
{
  protected function test(): void
  {
    $email=Config::get("admin_email");
    $session_id=Certificate::random_id(32);
    $extra=["email"=>$email,"session_id"=>$session_id];
    $expiry=86400*30; # 30 days
    $response=Certificate::issue(Config,$extra,$expiry,"session_token");
    if ($response["valid"]==true)
    {
      $url=Config::get("web_root")."/services/identity/index.php?action=verify";
      $response=$this->post($url,["email_token"=>$email_token]);
      $response=Certificate::verify(new Config(),$response);
    }
    $this->assertTrue(
      str_contains($response, '"valid":true'),
      "Verify should return valid:true",
      [$response]
    );
    $this->assertTrue(
      str_contains($response, '"email":"'.$payload["email"].'"'),
      "Verify should return the correct email",
      [$response]
    );
  }
}
