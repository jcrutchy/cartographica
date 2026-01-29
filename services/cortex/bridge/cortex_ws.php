<?php

require __DIR__ . '/../../../share/Autoload.php';

use cartographica\share\Env;
use cartographica\services\cortex\bridge\CortexWebSocketServer;

echo "Starting Cartographica Cortex Bridge Server...\n";

$ws = new CortexWebSocketServer(
    host: '0.0.0.0',
    port: 8081,
    cortexHost: '127.0.0.1',
    cortexPort: 5555
);

$ws->run();
