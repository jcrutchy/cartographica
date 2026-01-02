<?php

namespace cartographica\services\atlas\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\services\atlas\Certificate;

class VerifyCertificate
{
  private Request $req;

  public function __construct(Request $req)
  {
    $this->req = $req;
  }

  public function handle(): void
  {
    $certificateJson=$this->req->post("certificate");
    if (!$certificateJson)
    {
      Response::error("Missing certificate.");
    }
    $expiry=86400*30; # 30 days
    $result=Certificate::verify(new Config(),$certificateJson,$expiry);
    if ($result===false)
    {
      Response::error("Invalid certificate.");
    }
    Response::success($result);
  }
}
