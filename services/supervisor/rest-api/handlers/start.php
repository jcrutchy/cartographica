<?php

$socket = stream_socket_client("tcp://127.0.0.1:9001");

$request = json_encode([
    "action" => "start",
    "island_id" => $_GET["id"]
]);

fwrite($socket, $request . "\n");

$response = fgets($socket);
$data = json_decode($response, true);

echo json_encode($data);
