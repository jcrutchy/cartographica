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
    $tokenJson=$this->req->post("token");
    if (!$tokenJson)
    {
      Response::error("Missing token from emailed link.");
    }
    $token=json_decode($tokenJson, true);
    if (!is_array($token))
    {
      Response::error("Invalid token from emailed link [1].");
    }
    if (!isset($token["payload"]) || !isset($token["signature"]))
    {
      Response::error("Invalid token from emailed link [2].");
    }
    $payload=$token["payload"];
    $signature=$token["signature"];
    if (!is_array($payload) || !is_string($signature))
    {
      Response::error("Invalid token from emailed link [3].");
    }
    if (!isset($payload["expires_at"]))
    {
      Response::error("Invalid token from emailed link [4].");
    }
    if ($payload["expires_at"]<time())
    {
      Response::error("Emailed link token expired.");
    }
    $publicKey=@file_get_contents(Config::publicKey());
    if (!$publicKey)
    {
      Response::error("Identity service public key not available.");
    }
    $ok=Crypto::verify($payload, $signature, $publicKey);
    if (!$ok)
    {
      Response::error("Invalid token from emailed link [5].");
    }
    $deviceToken=Crypto::randomId(32);
    Response::json(["ok"=> true,"device_token"=>$deviceToken]);
  }
}
