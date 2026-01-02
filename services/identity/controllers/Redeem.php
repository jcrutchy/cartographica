<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Crypto;
use cartographica\services\identity\Config;

class Redeem
{
  private Request $req;

  public function __construct(Request $req)
  {
    $this->req=$req;
  }

  public function handle(): void
  {
    $tokenJson=$this->req->post("email_token");
    if (!$tokenJson)
    {
      Response::error("Missing email token.");
    }
    $expiry=600; # 10 minutes
    $result=Certificate::verify(new Config(),$tokenJson,$expiry);
    if ($result===false)
    {
      Response::error("Invalid email token.");
    }
    $payload=$result["payload"];
    $email=$payload["email"];
    $session_id=Crypto::randomId(32);
    $extra=["email"=>$email,"session_id"=>$session_id];
    $expiry=86400*30; # 30 days
    $device_token=Certificate::issue(Config,$extra,$expiry);


    $result["device_token"]=$deviceToken;
    Response::success($result);
  }
}
