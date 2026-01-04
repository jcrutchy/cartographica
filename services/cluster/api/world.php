<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/socket_client.php';

$DATA_PATH = realpath(__DIR__ . '/../../../cartographica_data/services/cluster');
$worldsDir = $DATA_PATH . '/worlds';

$worlds = [];

if (is_dir($worldsDir)) {
    foreach (glob($worldsDir . '/*.json') as $file) {
        $data = json_decode(file_get_contents($file), true);
        if (!isset($data['id'])) continue;
        $worlds[] = $data;
    }
}

json_response(['ok' => true, 'worlds' => $worlds]);
