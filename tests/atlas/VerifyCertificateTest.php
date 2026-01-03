<?php

namespace cartographica\tests\atlas;

use cartographica\tests\TestCase;
use cartographica\share\Http;
use cartographica\share\Certificate;
use cartographica\services\atlas\Config;

class VerifyCertificateTest extends TestCase
{
  protected function test(): void
  {

    $config=new Config();

    $owner_email=Config::get("admin_email");
    $island_name="Test Island";
    $public_key="<<TEST>>";
    $island_key=Certificate::random_id(64);

    $extra=["owner_email"=>$owner_email,"island_name"=>$island_name,"public_key"=>$public_key,"island_key"=>$island_key];
    $expiry=86400*90; # 90 days
    $response=Certificate::issue($config,$extra,$expiry,"island_certificate");
    if ($response["valid"]==true)
    {
      unset($response["valid"]);
      $certificate_json=json_encode($response);
      $url=Config::get("web_root")."/services/atlas/index.php?action=verify_certificate";
      $response=$this->post($url,["certificate"=>$certificate_json]);
    }

    $this->assertTrue(
      str_contains($response, '"valid":true'),
      "Verify should return valid:true",
      [$response]
    );
    $this->assertTrue(
      str_contains($response, '"owner_email":"'.$owner_email.'"'),
      "Verify should return the correct owner email",
      [$response]
    );
  }
}
