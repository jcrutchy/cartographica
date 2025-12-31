<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Crypto;
use cartographica\share\Logger;
use cartographica\services\identity\Config;
use cartographica\services\identity\Mailer;

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
        $token      = Crypto::sign($payload, $privateKey);

        // Build login link
        $link = Config::loginLinkBase() . urlencode($token);

        // Send email via Mailer helper
        $mailer = new Mailer();
        $mailer->sendLoginLink($email, $link);

        Logger::info("Login link sent to $email");

        Response::success(["status" => "sent"]);
    }
}
