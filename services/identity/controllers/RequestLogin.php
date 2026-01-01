<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Crypto;
use cartographica\share\Logger;
use cartographica\share\Db;
use cartographica\share\Smtp;
use cartographica\share\Template;
use cartographica\share\SharedConfig;
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
        $email = $this->req->post("email");

        if (!$email) {
            Response::error("Missing email");
        }

        // Build login payload
        $payload = [
            "email"      => $email,
            "issued_at"  => time(),
            "expires_at" => time() + 300 // 5 minutes
        ];

        // Sign with identity private key
        $privateKey = file_get_contents(Config::privateKey());
        $signature  = Crypto::sign($payload, $privateKey);

        // Build token object expected by Redeem
        $token = json_encode([
            "payload"   => $payload,
            "signature" => $signature
        ]);

        // Build login link
        $link = Config::loginLinkBase() . urlencode($token);

        // Send email: link to player, notification to admin
        $smtp=new Smtp();

        $html = Template::render(
            __DIR__ . "/../templates/login_email.html",
            [
                "email" => $email,
                "link"  => $link
            ]
        );
        $smtp->send($email,"Cartographica: login link",$html);

        $html = Template::render(
            __DIR__ . "/../templates/login_email_admin.html",
            [
                "email" => $email
            ]
        );
        $smtp->send(SharedConfig::get("admin_email"),"cartographica: email login requested",$html);

        // Log login attempt
        $pdo = Db::connect(Config::sqlitePath(),__DIR__."/../schema.sql");
        $stmt = $pdo->prepare("
            INSERT INTO login_attempts (email, requested_at, ip_address)
            VALUES (:email, :time, :ip)
        ");
        $stmt->execute([
            ":email" => $email,
            ":time"  => time(),
            ":ip"    => $this->req->ip()
        ]);

        Logger::info("Login link sent to $email");

        Response::success(["status" => "sent"]);
    }
}
