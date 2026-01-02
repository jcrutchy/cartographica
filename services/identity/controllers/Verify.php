<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Certificate;
use cartographica\share\Logger;
use cartographica\share\Request;
use cartographica\share\Response;
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
    $tokenJson=$this->req->post("session_token");
    if (!$tokenJson)
    {
      Response::error("Missing session token.");
    }
    $config=new Config();
    $result=Certificate::verify($config,$tokenJson);
    if ($result["valid"]==false)
    {
      Response::error($result["error"]);
    }
    $payload=$result["payload"];
    if ($payload["type"]!=="session_token")
    {
      Response::error("Invalid token type.");
    }
    if (!isset($payload["email"]))
    {
      Response::error("Session token missing email field.");
    }
    $email=$payload["email"];
    Logger::info("Session token for $email verified");
    Response::success($result);
  }
}
