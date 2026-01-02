<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\Certificate;
use cartographica\services\identity\Config;

class VerifyTest extends TestCase
{
  protected function test(): void
  {
    $extra=["email"=>Config::get("admin_email")];
    $expiry=600; # 10 minutes
    $device_token=Certificate::issue(Config,$extra,$expiry);
    if ($device_token!==false)
    {
      $url=Config::get("web_root")."/services/identity/index.php?action=verify";
      $response=$this->post($url,["device_token"=>$device_token]);
    }
    else
    {
      $response="error: unable to generate device_token";
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
