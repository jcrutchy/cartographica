<?php

declare(strict_types=1);

require_once __DIR__ . '/../lib/socket_client.php';

$action = $_GET['action'] ?? null;
$id     = $_GET['id'] ?? null;

if (!$action || !$id) {
    json_response(['ok' => false, 'error' => 'missing_params'], 400);
}

$valid = ['start', 'stop', 'restart'];
if (!in_array($action, $valid, true)) {
    json_response(['ok' => false, 'error' => 'invalid_action'], 400);
}

$resp = cluster_send_command([
    'command' => $action,
    'island'  => $id,
]);

json_response($resp, $resp['ok'] ? 200 : 500);
