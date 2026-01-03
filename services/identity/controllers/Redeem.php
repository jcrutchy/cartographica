<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Certificate;
use cartographica\share\Db;
use cartographica\share\Logger;
use cartographica\share\Request;
use cartographica\share\Response;
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
    $config=new Config();
    $result=Certificate::verify($config,$tokenJson);
    if ($result["valid"]==false)
    {
      Response::error($result["error"]);
    }
    $payload=$result["payload"];
    if ($payload["type"]!=="email_token")
    {
      Response::error("Invalid token type.");
    }
    if (!isset($payload["email"]))
    {
      Response::error("Email token missing email field.");
    }
    $email=$payload["email"];
    if (!isset($payload["player_id"]))
    {
      Response::error("Email token missing player_id field.");
    }
    if (!isset($payload["email_token_id"]))
    {
      Response::error("Email token missing email_token_id field.");
    }
    $player_id=$payload["player_id"];
    $session_id=Certificate::random_id(32);
    $extra=["email"=>$email,"session_id"=>$session_id,"player_id"=>$player_id];
    $expiry=86400*30; # 30 days
    $result=Certificate::issue($config,$extra,$expiry,"session_token");
    if ($result["valid"]==false)
    {
      Response::error($result["error"]);
    }
    $payload=$result["payload"];
    unset($result["valid"]);
    $session_token=json_encode($result);
    $pdo=Db::connect($config->sqlitePath(),__DIR__."/../schema.sql");
    $stmt=$pdo->prepare("INSERT INTO session_tokens (email,issued_at,expires_at,player_id,session_token) VALUES (:email,:issued_at,:expires_at,:player_id,:session_token)");
    $stmt->execute([":email"=>$email,":issued_at"=>$payload["issued_at"],":expires_at"=>$payload["expires_at"],":player_id"=>$payload["player_id"],":session_token"=>$session_token]);
    Logger::info("Session token issued for $email");
    Response::success(["session_token"=>$session_token]);
  }
}
