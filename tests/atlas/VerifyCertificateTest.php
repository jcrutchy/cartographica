<?php

namespace cartographica\tests\atlas;

use cartographica\tests\TestCase;
use cartographica\share\Request;
use cartographica\services\atlas\Certificate;

class VerifyCertificateTest extends TestCase
{
  protected function test(): void
  {
    $extra=["owner"=>Config::get("admin_email"),"name"=>"Test Island"];
    $expiry=86400*30; # 30 days
    $certificate=Certificate::issue(new Config(),$extra,$expiry);
    if ($certificate!==false)
    {
      $url=Config::get("web_root")."/services/atlas/index.php?action=verify_certificate";
      $response=$this->post($url,["certificate"=>$certificate]);
    }
    else
    {
      $response="error: unable to generate certificate";
    }
    $this->assertTrue(
      str_contains($response,'"ok":true'),
      "VerifyCertificate should return ok:true",
      [$response]
    );
    $this->assertTrue(
      str_contains($response, $extra["name"]),
      "VerifyCertificate should return payload",
      [$response]
    );
  }
}
