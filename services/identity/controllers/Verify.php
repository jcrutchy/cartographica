<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Crypto;
use cartographica\services\identity\Config;

class Verify {
    private Request $req;

    public function __construct(Request $req) {
        $this->req = $req;
    }

    public function handle(): void {
        $token = $this->req->post("device_token");

        if (!$token) {
            Response::error("Missing device_token");
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

        Response::success([
            "valid"   => true,
            "payload" => $payload
        ]);
    }
}
