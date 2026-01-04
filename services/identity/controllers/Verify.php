<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Certificate;
use cartographica\share\Logger;
use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\services\identity\Config;

# TODO: automatic renewing of token?

class Verify
{
  private Request $req;

  public function __construct(Request $req)
  {
    $this->req=$req;
  }

  public function handle(): void
  {
    $config=new Config();
    $validator=$config->validator();

    $tokenJson=$this->req->post("session_token");
    if (!$tokenJson)
    {
      Response::error("Missing session token.");
    }

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
    if ($payload["expires_at"]<time())
    {
      Response::error("Session token has expired.");
    }

    $validation=$validator->validateVerifyPayload($payload);
    if (!$validation["valid"])
    {
      Response::error($validation["payload"]);
    }
    $clean=$validation["payload"];
    $email=$clean["email"];

    Logger::info("Session token for $email verified");
    Response::success($result);
  }
}
