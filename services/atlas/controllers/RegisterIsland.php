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
    $this->req = $req;
  }

  public function handle(): void
  {
    $island_name=$this->req->post("island_name");
    $owner_email=$this->req->post("owner_email");
    $public_key=$this->req->post("public_key");
    $metadata=$this->req->post("metadata");
    if (empty($island_name) || empty($owner_email) || empty($public_key))
    {
      Response::error("Missing required fields: island_name, owner_email, public_key");
    }

    $owner_email=strtolower(trim($owner_email));
    if (!filter_var($owner_email,FILTER_VALIDATE_EMAIL))
    {
      Response::error("Invalid owner_email.");
    }

    if (strlen($public_key)>10000)
    {
      Response::error("public_key too large.");
    }
    $public_key=trim($public_key);
    if (!openssl_pkey_get_public($public_key))
    {
      Response::error("Invalid public_key format.");
    }

    if (!is_array($metadata))
    {
      Response::error("Metadata must be an array.");
    }
    if (count($metadata)>200)
    {
      Response::error("Metadata contains too many fields.");
    }
    ksort($metadata);
    $metadata_json=json_encode($metadata,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($metadata_json===false)
    {
      Response::error("Error encoding metadata as JSON.");
    }
    if (strlen($metadata_json)>20000)
    {
      Response::error("Metadata too large.");
    }

    $island_name=trim($island_name);
    if (empty($island_name))
    {
      Response::error("Island name must not contain only spaces.");
    }
    $len=strlen($island_name);
    if (($len<4) || ($len>200))
    {
      Response::error("Island name must be between 4 and 200 characters long.");
    }
    $pdo=Db::connect(Config::sqlitePath(),__DIR__."/../schema.sql");
    $stmt=$pdo->prepare("SELECT COUNT(*) FROM islands WHERE island_name = :island_name");
    $stmt->execute([":island_name"=>$island_name]);
    if ($stmt->fetchColumn() > 0)
    {
      Response::error("Island name already registered.");
    }

    $config=new Config();
    $island_key=Certificate::random_id(64);
    $extra=["owner_email"=>$owner_email,"island_name"=>$island_name,"public_key"=>$public_key,"island_key"=>$island_key];
    $expiry=86400*90; # 90 days
    $result=Certificate::issue($config,$extra,$expiry,"island_certificate");
    if ($result["valid"]==false)
    {
      Response::error($result["error"]);
    }

    $certificate=$result;
    unset($certificate["valid"]);
    $certificate_json=json_encode($certificate);

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
        ":certificate"=>$certificate_json,
        ":issued_at"=>$payload["issued_at"],
        ":expires_at"=>$payload["expires_at"],
        ":island_key"=>$island_key,
        ":metadata"=>$metadata_json
    ]);
    Logger::info("Island certificate issued for '$island_name' owned by $owner_email");
    Response::success($result);
  }
}
