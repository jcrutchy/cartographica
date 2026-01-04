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
    $this->req=$req;
  }

  public function handle(): void
  {
    $config=new Config();
    $validator=$config->validator();

    $certificate_json=$this->req->post("certificate");
    if (!$certificate_json)
    {
      Response::error("Missing certificate.");
    }

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

    $result=$validator->validateVerifyCertificatePayload($payload);
    if (!$result["valid"])
    {
      Response::error($result["payload"]);
    }

    Logger::info("Island certificate for '".$payload["island_name"]."' owned by ".$payload["owner_email"]." verified");
    Response::success($result);
  }
}
