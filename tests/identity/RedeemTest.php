<?php

namespace cartographica\tests\identity;

use cartographica\tests\TestCase;
use cartographica\share\Crypto;
use cartographica\services\identity\Config;

class RedeemTest extends TestCase
{
  protected function test(): void
  {
    $extra=["email"=>Config::get("admin_email")];
    $expiry=600; # 10 minutes
    $email_token=Certificate::issue(new Config(),$extra,$expiry);
    if ($email_token!==false)
    {
      $url=Config::get("web_root")."/services/identity/index.php?action=redeem";
      $response=$this->post($url,["email_token"=>$email_token]);
    }
    else
    {
      $response="error: unable to generate email_token";
    }
    $this->assertTrue(
      str_contains($response,'"ok":true'),
      "Redeem should return ok:true",
      [$response]
    );
    $this->assertTrue(
      str_contains($response,'device_token'),
      "Redeem should return a device_token",
      [$response]
    );
  }
}
