<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Crypto;
use cartographica\services\identity\Config;

class Redeem {
    private Request $req;

    public function __construct(Request $req) {
        $this->req = $req;
    }

    public function handle(): void {
        $token = $this->req->post("token");

        if (!$token) {
            Response::error("Missing token");
        }

        $publicKey = file_get_contents(Config::publicKey());

        $payload = json_decode(base64_decode($token), true);

        if (!is_array($payload)) {
            Response::error("Invalid token");
        }

        if (!Crypto::verify($payload, $token, $publicKey)) {
            Response::error("Invalid signature");
        }

        if ($payload["expires_at"] < time()) {
            Response::error("Token expired");
        }

        // Issue device token
        $devicePayload = [
            "email"      => $payload["email"],
            "issued_at"  => time(),
            "expires_at" => time() + 86400 * 30
        ];

        $privateKey = file_get_contents(Config::privateKey());
        $deviceToken = Crypto::sign($devicePayload, $privateKey);

        Response::success([
            "device_token" => $deviceToken,
            "payload"      => $devicePayload
        ]);
    }
}
