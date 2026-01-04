<?php

declare(strict_types=1);

function cluster_send_command(array $command): array
{
    $address = '127.0.0.1';
    $port    = 9400;

    $fp = @stream_socket_client("tcp://{$address}:{$port}", $errno, $errstr, 1);
    if (!$fp) {
        return ['ok' => false, 'error' => "connect_failed: {$errstr} ({$errno})"];
    }

    stream_set_timeout($fp, 2);

    fwrite($fp, json_encode($command) . "\n");
    $response = fgets($fp);
    fclose($fp);

    if ($response === false) {
        return ['ok' => false, 'error' => 'no_response'];
    }

    $data = json_decode(trim($response), true);
    if (!$data) {
        return ['ok' => false, 'error' => 'invalid_json_response'];
    }

    return $data;
}

function json_response($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
