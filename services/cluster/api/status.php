<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/socket_client.php';

$DATA_PATH  = realpath(__DIR__ . '/../../../cartographica_data/services/cluster');
$stateFile  = $DATA_PATH . '/cluster_state.json';

if (!file_exists($stateFile)) {
    json_response(['ok' => false, 'error' => 'no_state'], 503);
}

$state = json_decode(file_get_contents($stateFile), true);
if (!$state) {
    json_response(['ok' => false, 'error' => 'invalid_state'], 500);
}

json_response(['ok' => true, 'state' => $state]);
