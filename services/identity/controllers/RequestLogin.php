<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Certificate;
use cartographica\share\Db;
use cartographica\share\Logger;
use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Smtp;
use cartographica\share\Template;
use cartographica\services\identity\Config;

# TODO: rate‑limiting login requests

class RequestLogin
{
  private Request $req;

  public function __construct(Request $req)
  {
    $this->req = $req;
  }

  public function handle(): void
  {
    $email=$this->req->post("email");
    if (empty($email))
    {
      Response::error("Missing email.");
    }
    $email=strtolower(trim($email));
    if (!filter_var($email,FILTER_VALIDATE_EMAIL))
    {
      Response::error("Invalid email address.");
    }
    $domain=substr(strrchr($email,"@"),1);
    if ($domain && !checkdnsrr($domain,"MX"))
    {
      Response::error("Email domain not found.");
    }
    $config=new Config();
    $player_id=Certificate::random_id(16);
    $email_token_id=Certificate::random_id(16);
    $extra=["email"=>$email,"player_id"=>$player_id,"email_token_id"=>$email_token_id];
    $expiry=600; # 10 minutes
    $result=Certificate::issue($config,$extra,$expiry,"email_token");
    if ($result["valid"]==false)
    {
      Response::error($result["error"]);
    }
    unset($result["valid"]);
    $link=$config->loginLinkBase().urlencode(json_encode($result));
    $smtp=new Smtp();
    $html=Template::render(__DIR__."/../templates/login_email.html",["email"=>$email,"link"=>$link]);
    $smtp->send($email,"Cartographica: login link",$html);
    $html=Template::render(__DIR__."/../templates/login_email_admin.html",["email"=>$email]);
    $smtp->send($config->get("admin_email"),"cartographica: email login requested",$html);
    $pdo=Db::connect($config->sqlitePath(),__DIR__."/../schema.sql");
    $stmt=$pdo->prepare("INSERT INTO login_attempts (email,requested_at,ip_address,player_id,email_token_id) VALUES (:email,:time,:ip,:player_id,:email_token_id)");
    $stmt->execute([":email"=>$email,":time"=>time(),":ip"=>$this->req->ip(),":player_id"=>$player_id,":email_token_id"=>$email_token_id]);
    Logger::info("Login link sent to $email");
    Response::success(["status"=>"sent"]);
  }
}
