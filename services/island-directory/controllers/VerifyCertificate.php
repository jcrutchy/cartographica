<?php

namespace cartographica\services\islanddirectory\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\services\islanddirectory\Certificate;

class VerifyCertificate
{
    private Request $req;

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function handle(): void
    {
        $certificate = $this->req->post("certificate");

        if (!$certificate) {
            Response::error("Missing certificate");
        }

        $result = Certificate::verify($certificate);

        if (!$result["valid"]) {
            Response::error($result["error"]);
        }

        Response::success([
            "valid"   => true,
            "payload" => $result["payload"]
        ]);
    }
}
