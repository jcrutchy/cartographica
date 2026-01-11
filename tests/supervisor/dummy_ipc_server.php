<?php
// dummy_ipc_server.php
// Usage: php dummy_ipc_server.php [respond|delay|noreply] [port]
$mode = $argv[1] ?? 'respond';
$port = (int)($argv[2] ?? 9001);
$address = "tcp://127.0.0.1:$port";
$server = @stream_socket_server($address, $errno, $errstr);
if (!$server) {
    echo "Failed to bind: $errstr ($errno)\n";
    exit(1);
}
// Signal readiness to the parent via stdout
echo "READY\n";
fflush(STDOUT);

$client = @stream_socket_accept($server, 10);
if ($client === false) {
    echo "No client connected\n";
    fclose($server);
    exit(1);
}
// Read one line (the command)
$line = fgets($client);
// Echo received request for debugging
echo "REQ:" . trim($line) . "\n";
fflush(STDOUT);
if ($mode === 'respond') {
    fwrite($client, json_encode(['ok' => true]) . "\n");
} elseif ($mode === 'delay') {
    // Sleep longer than the REST handler read timeout (2s)
    sleep(5);
    fwrite($client, json_encode(['ok' => true]) . "\n");
} elseif ($mode === 'noreply') {
    // Don't write anything, just sleep then close
    sleep(6);
}
fflush($client);
fclose($client);
fclose($server);
exit(0);
