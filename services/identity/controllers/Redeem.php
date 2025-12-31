<?php

namespace cartographica\services\identity\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Crypto;
use cartographica\services\identity\Config;

class Redeem
{
    private Request $req;

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function handle(): void
    {
        //
        // 1. Get token from POST
        //
        $tokenJson = $this->req->post("token");

        if (!$tokenJson) {
            Response::error("Missing token from emailed link.");
        }

        //
        // 2. Decode token JSON
        //
        $token = json_decode($tokenJson, true);

        if (!is_array($token)) {
            Response::error("Invalid token from emailed link [1].");
        }

        if (!isset($token["payload"]) || !isset($token["signature"])) {
            Response::error("Invalid token from emailed link.");
        }

        $payload   = $token["payload"];
        $signature = $token["signature"];

        if (!is_array($payload) || !is_string($signature)) {
            Response::error("Invalid token from emailed link [2].");
        }

        //
        // 3. Load public key
        //
        $publicKey = @file_get_contents(Config::publicKey());
        if (!$publicKey) {
            Response::error("Identity public key not available.");
        }

        //
        // 4. Verify signature
        //
        $ok = Crypto::verify($payload, $signature, $publicKey);

        if (!$ok) {
            Response::error("Invalid token from emailed link [3].");
        }

        //
        // 5. Generate device token
        //
        $deviceToken = Crypto::randomId(32);

        //
        // 6. Return success
        //
        Response::json([
            "ok"           => true,
            "device_token" => $deviceToken
        ]);
    }
}
