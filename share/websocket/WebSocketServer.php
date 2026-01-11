<?php

namespace cartographica\share\websocket;

use cartographica\share\websocket\FrameDecoder;
use cartographica\share\websocket\Opcode;

class WebSocketServer
{
    protected $socket;
    protected array $clients = [];

    public function __construct(string $host, int $port)
    {

        // Create TCP server socket
        $this->socket = stream_socket_server(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            stream_context_create([
                'socket' => [
                    'ipv6_v6only' => false
                ]
            ])
        );

        if (!$this->socket) {
            throw new \Exception("Failed to create server socket: $errstr");
        }

        stream_set_blocking($this->socket, false);
    }

    public function run(): void
    {
        while (true) {
            $this->acceptNewClients();
            $this->readFromClients();
            usleep(10000); // 10ms tick
        }
    }

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
    
        $client = new WebSocketClient($conn, $this);
        $this->clients[] = $client;
        
        echo "New WebSocket client connected\n";      
    }

    protected function performHandshake($conn): bool
    {
        $headers = '';
        while (($line = fgets($conn)) !== false) {
            $line = rtrim($line);
            if ($line === '') break;
            $headers .= $line . "\n";
        }
    
        if (!preg_match('/Sec-WebSocket-Key: (.*)/', $headers, $matches)) {
            return false;
        }
    
        $key = trim($matches[1]);
        $accept = base64_encode(
            sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true)
        );
    
        $response =
            "HTTP/1.1 101 Switching Protocols\r\n" .
            "Upgrade: websocket\r\n" .
            "Connection: Upgrade\r\n" .
            "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
    
        fwrite($conn, $response);
        return true;
    }

    protected function readFromClients(): void
    {
        foreach ($this->clients as $client) {
            $msg = $client->read();
            if ($msg) {
                echo "Received message: " . json_encode($msg) . "\n";
            }
        }
    }

    public function removeClient(WebSocketClient $client): void
    {
        $this->clients = array_filter(
            $this->clients,
            fn($c) => $c !== $client
        );
    }
}
