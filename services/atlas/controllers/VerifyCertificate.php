<?php

namespace cartographica\services\atlas\controllers;

use cartographica\share\Certificate;
use cartographica\share\Logger;
use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\services\atlas\Config;

# TODO: automatic renewing of certificate?

class VerifyCertificate
{
  private Request $req;

  public function __construct(Request $req)
  {
    $this->req = $req;
  }

  public function handle(): void
  {
    $certificate_json=$this->req->post("certificate");
    if (!$certificate_json)
    {
      Response::error("Missing certificate.");
    }
    $config=new Config();
    $result=Certificate::verify($config,$certificate_json);
    if ($result["valid"]==false)
    {
      Response::error($result["error"]);
    }
    $payload=$result["payload"];
    if ($payload["type"]!=="island_certificate")
    {
      Response::error("Invalid certificate type.");
    }
    if ($payload["expires_at"]<time())
    {
      Response::error("Certificate has expired.");
    }
    if (!isset($payload["island_key"]))
    {
      Response::error("Certificate missing island_key field.");
    }
    if (!isset($payload["island_name"]))
    {
      Response::error("Certificate missing island_name field.");
    }
    $island_name=$payload["island_name"];
    if (!is_string($island_name) || strlen($island_name)<4 || strlen($island_name)>200)
    {
      Response::error("Invalid island_name in certificate.");
    }
    if (!isset($payload["owner_email"]))
    {
      Response::error("Certificate missing owner_email field.");
    }
    $owner_email=$payload["owner_email"];
    $owner_email=strtolower(trim($owner_email));
    if (!filter_var($owner_email,FILTER_VALIDATE_EMAIL))
    {
      Response::error("Invalid owner_email in certificate.");
    }
    Logger::info("Island certificate for '$island_name' owned by $owner_email verified");
    Response::success($result);
  }
}
