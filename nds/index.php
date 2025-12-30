<?php

header("Content-Type: application/json");

require __DIR__ . "/config.php";
require __DIR__ . "/lib/db.php";
require __DIR__ . "/lib/crypto.php";
require __DIR__ . "/lib/util.php";

$action = $_GET["action"] ?? null;
if (!$action) json_error("Missing action");

switch ($action) {

    // --------------------------------------------------------
    // 1. List networks
    // --------------------------------------------------------
    case "list_networks":
        $rows = db()->query("SELECT * FROM networks")->fetchAll(PDO::FETCH_ASSOC);
        json_ok($rows);
        break;

    // --------------------------------------------------------
    // 2. List nodes in a network
    // --------------------------------------------------------
    case "list_nodes":
        $network_id = $_GET["network_id"] ?? "";
        if (!$network_id) json_error("Missing network_id");

        $stmt = db()->prepare("SELECT * FROM nodes WHERE network_id = ?");
        $stmt->execute([$network_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        json_ok($rows);
        break;

    // --------------------------------------------------------
    // 3. Register or update a node
    // --------------------------------------------------------
    case "register_node":
        $payloadJson = $_POST["payload"] ?? "";
        $signature   = $_POST["signature"] ?? "";
    
        $payload = json_decode($payloadJson, true);
        if (!$payload) json_error("Invalid payload");
    
        if (!verify_token($payload, $signature)) {
            json_error("Invalid certificate signature");
        }
    
        if ($payload["type"] !== "node") {
            json_error("Certificate is not a node certificate");
        }
    
        // Extract fields
        $node_id     = $payload["node_id"];
        $network_id  = $payload["network_id"];
        $ws_url      = $payload["ws_url"];
        $seed        = $payload["seed"];
        $connections = json_encode($payload["connections"]);

        $db = db();

        // Create network if missing
        $stmt = $db->prepare("SELECT id FROM networks WHERE id = ?");
        $stmt->execute([$network_id]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO networks (id, name, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$network_id, $network_id, time()]);
        }
    
        // Insert or update node
        $stmt = $db->prepare("
            INSERT INTO nodes (node_id, network_id, ws_url, seed, connections, status, registered_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'online', ?, ?)
            ON CONFLICT(node_id) DO UPDATE SET
                ws_url      = excluded.ws_url,
                seed        = excluded.seed,
                connections = excluded.connections,
                status      = 'online',
                updated_at  = excluded.updated_at
        ");
    
        $now = time();
        $stmt->execute([$node_id, $network_id, $ws_url, $seed, $connections, $now, $now]);
    
        json_ok(["registered" => true]);
        break;

    default:
        json_error("Unknown action");
}
