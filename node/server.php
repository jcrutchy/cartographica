<?php

require __DIR__ . "/lib/crypto.php";
require __DIR__ . "/lib/world.php";
require __DIR__ . "/lib/websocket.php";
require __DIR__ . "/lib/util.php";
require __DIR__ . "/lib/keys.php";
require __DIR__ . "/lib/logger.php";
require __DIR__ . "/config.php";
require __DIR__ . "/lib/PlayerManager.php";
require __DIR__ . "/lib/DB.php";

DB::init(DB_PATH, __DIR__ . "/data/schema.sql");

$playerManager = new PlayerManager();

Logger::init(SERVER_LOG_FILE);

$STATE = [];

$server = new WebSocketServer('0.0.0.0', 8080, [
    'max_message_bytes' => 8 * 1024 * 1024,
    'tick_ms'           => 100,
    'subprotocols'      => ['cartographica-v1'],
]);

$server->onOpen = function (int $clientId, array $clientInfo) use (&$STATE, $server) {
    $STATE[$clientId] = [
        'authed' => false,
        'player' => null,
    ];

    $server->send($clientId, [
        'type' => 'HELLO',
        'node' => NODE_ID,
    ]);
};

$server->onMessage = function (int $cid, string $payload) use (&$STATE, $server, $playerManager)
{
    $msg = json_decode($payload, true);
    if (!is_array($msg) || !isset($msg["type"]))
    {
        return;
    }

    switch ($msg["type"])
    {
        case "AUTH":
            handle_auth($server, $cid, $msg, $STATE, $playerManager);
            break;

        case "PING":
            $server->send($cid, ["type" => "PONG"]);
            break;

        case "REQUEST_WORLD":
            handle_request_world($server, $cid, $STATE);
            break;

        default:
            $server->send($cid,["type" => "ERROR","msg" => "Unknown message type."]);
    }
};

$server->onClose = function (int $cid, ?int $code) use (&$STATE, $playerManager) {
    if (isset($STATE[$cid]["player"])) {
        $player = $STATE[$cid]["player"];
        $playerManager->save($player["player_id"], $player);
        $playerManager->unload($player["player_id"]);
    }
    unset($STATE[$cid]);
};

$server->onTick = function () use (&$STATE, $server) {
    // periodic tasks if you want
};

// ------------------------------------------------------------
// AUTH HANDLER
// ------------------------------------------------------------

function handle_auth(WebSocketServer $server, int $cid, array $msg, array &$STATE, PlayerManager $playerManager): void
{
    $payload   = $msg["payload"]   ?? null;
    $signature = $msg["signature"] ?? null;

    if (!$payload || !$signature) {
        $server->send($cid, ["type" => "ERROR", "msg" => "Missing auth fields"]);
        return;
    }

    if (!verify_identity_token($payload, $signature)) {
        $server->send($cid, ["type" => "ERROR", "msg" => "Invalid signature"]);
        return;
    }

    if ($payload["expires_at"] < time()) {
        $server->send($cid, ["type" => "ERROR", "msg" => "Token expired"]);
        return;
    }

    $playerId = $payload["player_id"];
    $player   = $playerManager->load($playerId);

    $STATE[$cid]["authed"] = true;
    $STATE[$cid]["player"] = $player;

    $server->send($cid, [
        "type"   => "AUTH_OK",
        "player" => $player
    ]);
}

// ------------------------------------------------------------
// WORLD REQUEST HANDLER
// ------------------------------------------------------------

function handle_request_world(WebSocketServer $server, int $cid, array &$STATE): void
{
    if (!($STATE[$cid]["authed"] ?? false)) {
        $server->send($cid, [
            "type" => "ERROR",
            "msg"  => "Not authenticated"
        ]);
        return;
    }

    // Temporary placeholder world
    $world = [
        "seed" => 12345,
        "chunks" => [
            ["x" => 0, "y" => 0, "terrain" => "grass"],
            ["x" => 1, "y" => 0, "terrain" => "forest"],
        ]
    ];

    $server->send($cid, [
        "type"  => "WORLD_DATA",
        "world" => $world
    ]);
}


function register_with_nds(array $cert): void {
    $res = http_post(NDS_URL . "?action=register_node", [
        "payload"   => json_encode($cert["payload"]),
        "signature" => $cert["signature"]
    ]);

    if (!$res["ok"]) {
        throw new Exception("NDS error: " . $res["error"]);
    }
    Logger::info("Node registered with NDS.");
}


function request_node_certificate(): array {
    $publicKey = file_get_contents(NODE_PUBLIC_KEY);

    $res = http_post(IDENTITY_URL . "?action=issue_node_certificate", [
        "public_key"  => $publicKey,
        "node_id"     => NODE_ID,
        "network_id"  => NETWORK_ID,
        "ws_url"      => NODE_WS_URL,
        "seed"        => NODE_SEED,
        "connections" => json_encode([]) // later: real topology
    ]);

    if (!$res["ok"]) {
        throw new Exception("Identity error: " . $res["error"]);
    }

    return $res["data"]; // payload + signature
}


// ------------------------------------------------------------
// START SERVER
// ------------------------------------------------------------

Logger::info("Cartographica Node Server starting…");
Logger::info("Listening on ".NODE_WS_URL);

if ((file_exists(NODE_PRIVATE_KEY)==false) or (file_exists(NODE_PUBLIC_KEY)==false))
{
  generate_node_keys();
}

try {
    $cert = request_node_certificate();
    register_with_nds($cert);
} catch (Exception $e) {
    echo "Startup failed: " . $e->getMessage() . "\n";
    exit(1);
}
$server->run();
