<?php

namespace cartographica\tests\atlas;

use cartographica\tests\TestCase;
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

    // Issue certificate
    $extra = [
      "owner_email"=>$owner_email,
      "island_name"=>$island_name,
      "public_key"=>$public_key,
      "island_key"=>$island_key
    ];
    $expiry=86400*90; # 90 days
    $issued=Certificate::issue($config,$extra,$expiry,"island_certificate");
    $this->assertTrue($issued["valid"],"certificate should be valid",$issued);

    unset($issued["valid"]);
    $certificate_json=json_encode($issued);

    // Call verify endpoint
    $url=Config::get("web_root")."/services/atlas/index.php?action=verify_certificate";
    $response_raw=$this->post($url,["certificate"=>$certificate_json]);

    // Parse JSON
    $response=$this->assertJson($response_raw);

    // Should return ok:true
    $this->assertStatusOK($response);

    // Should contain valid:true
    if ($this->assertArrayHasKey("valid",$response))
    {
      $this->assertTrue($response["valid"]);
    }

    // Should contain payload
    if ($this->assertArrayHasKey("payload",$response))
    {
      // Should contain correct owner_email inside payload
      $payload=$response["payload"];
      if ($this->assertArrayHasKey("owner_email",$payload))
      {
        $this->assertEquals($owner_email,$payload["owner_email"]);
      }
    }
  }
}
