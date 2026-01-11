<?php

// Connect to the supervisor IPC server with a short timeout
$socket = @stream_socket_client("tcp://127.0.0.1:9001", $errno, $errstr, 2);
if (!$socket) {
    echo json_encode(["ok" => false, "error" => "ipc_connect_failed", "message" => "$errstr ($errno)"]);
    exit;
}
stream_set_timeout($socket, 2);

$request = json_encode([
    "action" => "stop",
    "island_id" => $_GET["id"],
    "timeout" => isset($_GET['timeout']) ? intval($_GET['timeout']) : 5
]);

fwrite($socket, $request . "\n");

$response = fgets($socket);
if ($response === false) {
    echo json_encode(["ok" => false, "error" => "no_response"]);
    fclose($socket);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    echo json_encode(["ok" => false, "error" => "invalid_response", "raw" => $response]);
    fclose($socket);
    exit;
}

fclose($socket);
echo json_encode($data);
