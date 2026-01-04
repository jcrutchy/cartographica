<?php

namespace cartographica\services\atlas\controllers;

use cartographica\share\Certificate;
use cartographica\share\Db;
use cartographica\share\Logger;
use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\services\atlas\Config;

class RegisterIsland
{
  private Request $req;

  public function __construct(Request $req)
  {
    $this->req=$req;
  }

  public function handle(): void
  {
    $config=new Config();
    $pdo=$config->pdo();
    $validator=$config->validator($pdo);

    $island_name=$this->req->post("island_name");
    $owner_email=$this->req->post("owner_email");
    $public_key=$this->req->post("public_key");
    $metadata=$this->req->post("metadata");
    if (empty($island_name) || empty($owner_email) || empty($public_key))
    {
      Response::error("Missing required fields: island_name, owner_email, public_key");
    }

    $payload=["island_name"=>$island_name,"owner_email"=>$owner_email,"public_key"=>$public_key,"metadata"=>$metadata];
    $result=$validator->validateCertificatePayload($payload);
    if (!$result["valid"])
    {
      Response::error($result["payload"]);
    }
    $payload=$result["payload"];
    $island_name=$payload["island_name"];
    $owner_email=$payload["owner_email"];
    $public_key=$payload["public_key"];
    $metadata=$payload["metadata"];

    $island_key=Certificate::random_id(64);
    $extra=["owner_email"=>$owner_email,"island_name"=>$island_name,"public_key"=>$public_key,"island_key"=>$island_key];
    $expiry=86400*90; # 90 days
    $result=Certificate::issue($config,$extra,$expiry,"island_certificate");
    if ($result["valid"]==false)
    {
      Response::error($result["error"]);
    }
    unset($result["valid"]);
    $certificate=json_encode($result);
    $stmt=$pdo->prepare("
      INSERT INTO islands (
        island_name,
        owner_email,
        public_key,
        certificate,
        issued_at,
        expires_at,
        island_key,
        metadata
      ) VALUES (
        :island_name,
        :owner_email,
        :public_key,
        :certificate,
        :issued_at,
        :expires_at,
        :island_key,
        :metadata
      )
    ");
    $payload=$result["payload"];
    $stmt->execute([
        ":island_name"=>$island_name,
        ":owner_email"=>$owner_email,
        ":public_key"=>$public_key,
        ":certificate"=>$certificate,
        ":issued_at"=>$payload["issued_at"],
        ":expires_at"=>$payload["expires_at"],
        ":island_key"=>$island_key,
        ":metadata"=>$metadata_json
    ]);
    Logger::info("Island certificate issued for '$island_name' owned by $owner_email");
    Response::success(["certificate"=>$certificate]);
  }
}
