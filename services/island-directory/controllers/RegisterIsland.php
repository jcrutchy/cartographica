<?php

namespace cartographica\services\islanddirectory\controllers;

use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\services\islanddirectory\Db;
use cartographica\services\islanddirectory\Certificate;

class RegisterIsland
{
    private Request $req;

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    public function handle(): void
    {
        $data = $this->req->data();

        $name       = $data["name"]       ?? null;
        $ownerEmail = $data["owner"]      ?? null;
        $publicKey  = $data["public_key"] ?? null;
        $metadata   = $data["metadata"]   ?? null;

        if (!$name || !$ownerEmail || !$publicKey) {
            Response::error("Missing required fields: name, owner, public_key");
        }

        // Issue certificate
        $cert = Certificate::issue($publicKey, $name, $ownerEmail);

        // Store in SQLite using the shared DB helper
        $pdo = Db::conn();

        $stmt = $pdo->prepare("
            INSERT INTO islands (
                name,
                owner_email,
                public_key,
                certificate,
                issued_at,
                expires_at,
                metadata
            ) VALUES (
                :name,
                :owner_email,
                :public_key,
                :certificate,
                :issued_at,
                :expires_at,
                :metadata
            )
        ");

        $stmt->execute([
            ":name"         => $name,
            ":owner_email"  => $ownerEmail,
            ":public_key"   => $publicKey,
            ":certificate"  => $cert["certificate"],
            ":issued_at"    => $cert["payload"]["issued_at"],
            ":expires_at"   => $cert["payload"]["expires_at"],
            ":metadata"     => $metadata ? json_encode($metadata) : null
        ]);

        Response::success([
            "certificate" => $cert["certificate"],
            "payload"     => $cert["payload"]
        ]);
    }
}
