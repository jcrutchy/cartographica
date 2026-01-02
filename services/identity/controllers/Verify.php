<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Crypto;
use cartographica\services\identity\Config;

class Verify
{
  private Request $req;

  public function __construct(Request $req)
  {
    $this->req=$req;
  }

  public function handle(): void
  {
    $tokenJson=$this->req->post("device_token");
    if (!$tokenJson)
    {
      Response::error("Missing device token.");
    }
    $expiry=600; # 10 minutes
    $result=Certificate::verify(new Config(),$tokenJson,$expiry);
    if ($result===false)
    {
      Response::error("Invalid device token.");
    }
    Response::success($result);
  }
}
