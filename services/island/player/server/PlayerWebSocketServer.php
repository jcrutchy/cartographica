<?php

namespace cartographica\services\island\player\server;

use cartographica\services\island\world\state\IslandState;
use cartographica\share\websocket\WebSocketServer;
use cartographica\services\island\Config;

class PlayerWebSocketServer extends WebSocketServer
{
    private IslandState $state;
    private PlayerMessageRouter $router;
    private Config $config;

    public function __construct(string $host, int $port, Config $config)
    {
        $this->state = new IslandState();
        $this->router = new PlayerMessageRouter($config);
        $this->config = $config;
        parent::__construct($host,$port);
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
    
        $client = new PlayerWebSocketClient($conn, $this); // your subclass
        $this->clients[] = $client;

        echo "New WebSocket client connected\n";
    
        $client->send([
            'type' => 'HELLO',
            'island_id' => 'island_01'
        ]);

    }

    protected function readFromClients(): void
    {
        foreach ($this->clients as $client) {
            $msg = $client->read();
            if ($msg) {
                echo "Received message: " . json_encode($msg) . "\n";
                $this->router->route($client, $msg, $this->state);
            }
        }
    }
}
