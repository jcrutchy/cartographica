<?php
// identity/index.php

header("Content-Type: application/json");

// ------------------------------------------------------------
// Load config + libraries
// ------------------------------------------------------------
require __DIR__ . "/db.php";
require __DIR__ . "/crypto.php";
require __DIR__ . "/email.php";
require __DIR__ . "/util.php";
require __DIR__ . "/smtp.php";
require __DIR__ . "/error.php";
require __DIR__ . "/config.php";

set_error_handler("error_handler",E_ALL);
set_exception_handler("exception_handler");

$action = $_GET["action"] ?? null;
if (!$action) {
    json_error("Missing action");
}

// ------------------------------------------------------------
// ROUTER
// ------------------------------------------------------------
switch ($action) {

    // --------------------------------------------------------
    // 1. Request login link
    // --------------------------------------------------------
    case "request_login":
        $email = trim($_POST["email"] ?? "");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error("Invalid email");
        }

        $token = bin2hex(random_bytes(32));
        $expires = time() + 600; // 10 minutes

        $db = db();
        $stmt = $db->prepare("INSERT INTO login_links (token, email, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$token, $email, $expires]);

        send_login_email($email, $token);

        json_ok(["message" => "Login link sent"]);
        break;


    // --------------------------------------------------------
    // 2. Redeem login link → issue device token
    // --------------------------------------------------------
    case "redeem":
        $token = $_GET["token"] ?? "";
        if (!$token) json_error("Missing token");

        $db = db();
        $stmt = $db->prepare("SELECT * FROM login_links WHERE token = ? AND used = 0");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) json_error("Invalid or used token");
        if ($row["expires_at"] < time()) json_error("Token expired");

        // Mark token as used
        $db->prepare("UPDATE login_links SET used = 1 WHERE token = ?")->execute([$token]);

        $email = $row["email"];

        // Fetch or create user
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $player_id = bin2hex(random_bytes(32)); // permanent identity
            $stmt = $db->prepare("INSERT INTO users (email, player_id, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $player_id, time()]);
            $user_id = $db->lastInsertId();
        } else {
            $user_id = $user["id"];
            $player_id = $user["player_id"];
        }

        // Issue long-lived device token
        $deviceToken = bin2hex(random_bytes(32));
        $issued_at = time();
        $expires_at = time() + (86400 * 180); // 6 months

        $payload = [
            "player_id"  => $player_id,
            "issued_at"  => $issued_at,
            "expires_at" => $expires_at
        ];

        $signature = sign_payload($payload);

        $stmt = $db->prepare("
            INSERT INTO device_tokens (token, user_id, issued_at, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$deviceToken, $user_id, $issued_at, $expires_at]);

        json_ok([
            "token"     => $deviceToken,
            "payload"   => $payload,
            "signature" => $signature
        ]);
        break;


    // --------------------------------------------------------
    // 3. Verify device token (optional endpoint)
    // --------------------------------------------------------
    case "verify":
        $token = $_POST["token"] ?? "";
        $payload = json_decode($_POST["payload"] ?? "{}", true);
        $signature = $_POST["signature"] ?? "";

        if (!$token || !$payload || !$signature) {
            json_error("Missing fields");
        }

        if (!verify_payload($payload, $signature)) {
            json_error("Invalid signature");
        }

        if ($payload["expires_at"] < time()) {
            json_error("Token expired");
        }

        json_ok(["valid" => true, "player_id" => $payload["player_id"]]);
        break;


    case "issue_node_certificate":
        $publicKey = $_POST["public_key"] ?? "";
        $node_id   = $_POST["node_id"] ?? "";
        $network_id = $_POST["network_id"] ?? "";
        $ws_url    = $_POST["ws_url"] ?? "";
        $seed      = (int)($_POST["seed"] ?? 0);
        $connections = json_decode($_POST["connections"] ?? "[]", true);
    
        if (!$publicKey || !$node_id || !$network_id || !$ws_url) {
            json_error("Missing fields");
        }
    
        $now = time();
    
        $payload = [
            "type"        => "node",
            "node_id"     => $node_id,
            "network_id"  => $network_id,
            "public_key"  => $publicKey,
            "ws_url"      => $ws_url,
            "seed"        => $seed,
            "connections" => $connections,
            "issued_at"   => $now,
            "expires_at"  => $now + 86400 // 24 hours
        ];
    
        $signature = sign_payload($payload);
    
        json_ok([
            "payload"   => $payload,
            "signature" => $signature
        ]);





    // --------------------------------------------------------
    // Unknown action
    // --------------------------------------------------------
    default:
        json_error("Unknown action");
}
