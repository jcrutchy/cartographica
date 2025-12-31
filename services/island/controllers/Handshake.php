<?php

namespace cartographica\services\island\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\share\Http;
use cartographica\services\island\Config;

class Handshake
{
    private Request $req;

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function handle(): void
    {
        $deviceToken = $this->req->post("device_token");

        if (!$deviceToken) {
            Response::error("Missing device_token");
        }

        // Verify device token with Identity Service
        $verify = Http::post(Config::identityUrl(), [
            "action" => "verify",
            "data" => [
                "device_token" => $deviceToken
            ]
        ]);

        if (!$verify["ok"]) {
            Response::error("Invalid device token");
        }

        // Load island certificate
        $certificate = file_get_contents(Config::publicKey());

        // Load island metadata
        $config = json_decode(file_get_contents(Config::islandConfig()), true);

        Response::success([
            "device" => $verify["data"]["payload"],
            "island" => [
                "certificate" => $certificate,
                "metadata"    => $config
            ]
        ]);
    }
}
