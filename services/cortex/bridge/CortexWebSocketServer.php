<?php

namespace cartographica\services\cortex\bridge;

use cartographica\share\websocket\WebSocketServer;

class CortexWebSocketServer extends WebSocketServer
{
    private string $cortexHost;
    private int $cortexPort;

    /** @var resource|null */
    private $cortexSock = null;

    public function __construct(string $host, int $port, string $cortexHost, int $cortexPort)
    {
        $this->cortexHost = $cortexHost;
        $this->cortexPort = $cortexPort;

        parent::__construct($host, $port);
    }

    /* -------------------------------------------------------------
       Persistent Cortex connection
       ------------------------------------------------------------- */

    private function ensureCortexConnected(): bool
    {
        // Already connected and not EOF
        if ($this->cortexSock && !feof($this->cortexSock)) {
            return true;
        }

        // Attempt reconnect
        $this->cortexSock = @stream_socket_client(
            "tcp://{$this->cortexHost}:{$this->cortexPort}",
            $errno,
            $errstr,
            1 // 1 second timeout
        );

        if (!$this->cortexSock) {
            echo "Cortex reconnect failed: $errstr\n";
            $this->cortexSock = null;
            return false;
        }

        stream_set_blocking($this->cortexSock, false);
        echo "Connected to Cortex backend\n";
        return true;
    }

    /* -------------------------------------------------------------
       WebSocket server overrides
       ------------------------------------------------------------- */

    protected function acceptNewClients(): void
    {
        $conn = @stream_socket_accept($this->socket, 5);
        if (!$conn) return;

        stream_set_blocking($conn, true);

        if (!$this->performHandshake($conn)) {
            fclose($conn);
            return;
        }

        stream_set_blocking($conn, false);

        $client = new CortexWebSocketClient($conn, $this);
        $this->clients[] = $client;

        echo "New Cortex WebSocket client connected\n";

        $client->send([
            'type' => 'HELLO',
            'service' => 'cortex'
        ]);
    }

    protected function readFromClients(): void
    {
        foreach ($this->clients as $client) {
            $msg = $client->read();
            if ($msg) {
                echo "WS → Cortex: " . json_encode($msg) . "\n";
                $this->handleMessage($client, $msg);
            }
        }
    }

    private function handleMessage(CortexWebSocketClient $client, array $msg): void
    {

        $start = microtime(true);
        $response = $this->sendToCortex($msg);
        $elapsed = (microtime(true) - $start) * 1000;
        echo "Cortex RTT: {$elapsed}ms\n";

        $client->send($response);
    }

    /* -------------------------------------------------------------
       Persistent Cortex RPC
       ------------------------------------------------------------- */

    private function sendToCortex(array $msg): array
    {
        if (!$this->ensureCortexConnected()) {
            return ['error' => 'Cortex unavailable'];
        }

        // Send JSON line
        fwrite($this->cortexSock, json_encode($msg) . "\n");

        // Read response with small timeout
        $resp = '';
        $start = microtime(true);

        while (true) {
            $chunk = fgets($this->cortexSock);

            if ($chunk !== false) {
                $resp .= $chunk;

                // Full line received
                if (str_ends_with($chunk, "\n")) {
                    break;
                }
            }

            // 100ms timeout
            if ((microtime(true) - $start) > 0.1) {
                break;
            }

            // Sleep ~0.1ms
            usleep(100);
        }

        // Detect broken connection
        if (feof($this->cortexSock)) {
            fclose($this->cortexSock);
            $this->cortexSock = null;
            return ['error' => 'Cortex disconnected'];
        }

        if (!$resp) {
            return ['error' => 'No response from Cortex'];
        }

        return json_decode($resp, true) ?? ['error' => 'Invalid JSON from Cortex'];
    }
}
