<?php

// ------------------------------------------------------------
// Cartographica Island Server Bootstrap
// ------------------------------------------------------------

// 1. Use the shared autoloader
require __DIR__ . '/../../share/Autoload.php';

// 2. Import classes
use cartographica\services\island\player\server\PlayerWebSocketServer;

// 3. Configuration
$host = "[::]";
$port = 8080;

echo "Starting Cartographica Island Server...\n";
echo "Listening on ws://{$host}:{$port}\n";

// 4. Create the server instance
$server = new PlayerWebSocketServer($host, $port);

// 5. Run the server loop
$server->run();
