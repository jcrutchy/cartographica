<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Logger;
use cartographica\share\Db;
use cartographica\share\Smtp;
use cartographica\share\Template;
use cartographica\services\identity\Config;

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
    if (!$email)
    {
      Response::error("Missing email.");
    }
    $extra=["email"=>$email];
    $expiry=600; # 10 minutes
    $email_token=Certificate::issue(new Config(),$extra,$expiry);
    if ($email_token===false)
    {
      Response::error("Unable to generate token.");
    }
    $link=Config::loginLinkBase().urlencode($email_token);
    $smtp=new Smtp();
    $html=Template::render(__DIR__."/../templates/login_email.html",["email"=>$email,"link"=>$link]);
    $smtp->send($email,"Cartographica: login link",$html);
    $html=Template::render(__DIR__."/../templates/login_email_admin.html",["email"=>$email]);
    $smtp->send(Config::get("admin_email"),"cartographica: email login requested",$html);
    $pdo=Db::connect(Config::sqlitePath(),__DIR__."/../schema.sql");
    $stmt=$pdo->prepare("INSERT INTO login_attempts (email,requested_at,ip_address) VALUES (:email,:time,:ip)");
    $stmt->execute([":email"=>$email,":time"=>time(),":ip"=>$this->req->ip()]);
    Logger::info("Login link sent to $email");
    Response::success(["status"=>"sent"]);
  }
}
