<?php

// ------------------------------------------------------------
// Cartographica Island Server Bootstrap
// ------------------------------------------------------------

// 1. Use the shared autoloader
require __DIR__ . '/../../share/Autoload.php';

// 2. Import classes
use cartographica\services\island\player\server\PlayerWebSocketServer;
use cartographica\share\Env;
use cartographica\services\island\Config;

$islandId = $argv[1] ?? null;

if (!$islandId) {
    echo "Usage: php script.php <island_id>\n";
    exit(1);
}

echo "Launching island: {$islandId}\n";

$config=new Config($islandId);
$config->loadIslandConfig();

// 3. Configuration
$host = "[::]";
$port = $config->get("port");

echo "Starting Cartographica Island Server...\n";
echo "Listening on ws://{$host}:{$port}\n";

// 4. Create the server instance
$server = new PlayerWebSocketServer($host, $port, $config);

// 5. Run the server loop
$server->run();
