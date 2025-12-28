<?php
// Run this in terminal: php NPC_Agent_CLI.php

require 'vendor/autoload.php';
require 'NPCBrain.php';

use WebSocket\Client;
use WebSocket\ConnectionException;

// --- CONFIGURATION ---
const SERVER_URL = "ws://127.0.0.1:8080/game-authority";
const AUTH_TOKEN = "trusted_npc_token_x99"; // Trusted Authority Key
const TICK_RATE_MS = 100; // Run loop every 100ms (10 ticks/sec)

class AutonomousAgent {
    private Client $client;
    private NPCBrain $brain;
    private string $agentId;
    private bool $running = true;

    public function __construct(string $agentId) {
        $this->agentId = $agentId;
        
        // Initialize the Neural Network (3 Inputs -> 5 Hidden -> 3 Outputs)
        // Inputs: [Health, EnemyDistance, ResourceDistance]
        // Outputs: [Attack, Flee, Gather]
        $this->brain = new NPCBrain(3, 5, 3);
        
        echo "[System] Agent {$this->agentId} initialized.\n";
    }

    public function connect(): void {
        echo "[Network] Connecting to Trusted Authority at " . SERVER_URL . "...\n";
        
        try {
            // Options: Set a timeout so receive() doesn't block forever, allowing our loop to tick
            $this->client = new Client(SERVER_URL, [
                'timeout' => 0.1, // Non-blocking-ish read
                'headers' => ['Authorization' => AUTH_TOKEN]
            ]);
            
            // Handshake / Login
            $this->client->send(json_encode([
                'type' => 'LOGIN',
                'id' => $this->agentId
            ]));
            
            echo "[Network] Connected securely.\n";
            
        } catch (Exception $e) {
            die("[Error] Could not connect: " . $e->getMessage() . "\n");
        }
    }

    public function run(): void {
        echo "[System] Starting Main Loop (CLI Mode)...\n";

        while ($this->running) {
            $start = microtime(true);

            // 1. NETWORK: Check for World Updates
            $worldData = $this->listen();

            if ($worldData) {
                // 2. COGNITION: Process inputs via Neural Net
                $inputs = $this->normalizeInputs($worldData);
                $decision = $this->brain->think($inputs);

                // 3. ACTION: Send decision back to server
                $actionPacket = $this->interpretOutput($decision);
                $this->act($actionPacket);
            }

            // 4. TICK RATE CONTROL
            // Calculate how much time is left in this tick to maintain stable FPS
            $elapsed = (microtime(true) - $start) * 1000; // in ms
            $sleep = max(0, TICK_RATE_MS - $elapsed);
            
            if ($sleep > 0) {
                usleep((int)($sleep * 1000));
            }
        }
    }

    /**
     * Listen for WebSocket messages without crashing on timeout
     */
    private function listen(): ?array {
        try {
            $payload = $this->client->receive();
            if (!$payload) return null;
            return json_decode($payload, true);
        } catch (ConnectionException $e) {
            // Timeouts are expected in a game loop, connection drops are not.
            if (strpos($e->getMessage(), 'Empty read') !== false) {
                return null; // No data this tick
            }
            echo "[Warning] Connection jitter: " . $e->getMessage() . "\n";
            // In a real app, implement reconnect logic here
            return null;
        }
    }

    /**
     * Convert raw game data (JSON) into Float Array (0.0 - 1.0) for the Brain
     */
    private function normalizeInputs(array $data): array {
        // Example: Server sends {'hp': 50, 'enemy_dist': 200, 'gold_dist': 5}
        // We normalize these to 0.0 - 1.0 ranges
        
        $hp = ($data['hp'] ?? 0) / 100; 
        $enemy = 1 - min(1, ($data['enemy_dist'] ?? 1000) / 1000); // Closer = Higher input
        $gold = 1 - min(1, ($data['gold_dist'] ?? 1000) / 1000);

        return [$hp, $enemy, $gold];
    }

    /**
     * Convert Brain Output (Float Array) to Game Command
     */
    private function interpretOutput(array $outputs): array {
        $actions = ['ATTACK', 'FLEE', 'GATHER'];
        
        // Get index of highest activation
        $maxVal = -1;
        $idx = 0;
        foreach ($outputs as $k => $v) {
            if ($v > $maxVal) {
                $maxVal = $v;
                $idx = $k;
            }
        }

        return [
            'type' => 'COMMAND',
            'action' => $actions[$idx],
            'confidence' => $maxVal
        ];
    }

    private function act(array $packet): void {
        // Only act if confidence is high enough? (Emergent behavior nuance)
        if ($packet['confidence'] > 0.5) {
            // echo "[Action] Decided to {$packet['action']} ({$packet['confidence']})\n";
            $this->client->send(json_encode($packet));
        }
    }
}

// --- ENTRY POINT ---
$npc = new AutonomousAgent("NPC_Alpha_01");
$npc->connect();
$npc->run();


/*

Why this is performant for PHP
1. JIT Compiler (PHP 8.0+): When you run this in CLI with Opcache enabled, the NPCBrain math (the
matrix multiplication loops) gets compiled down to machine code. It is surprisingly fast.
2. Memory Persistence: Unlike a web request where PHP tears down everything after 200ms, this script
keeps the $brain object in RAM. It "learns" and "remembers" (if you add recurrent memory) without
hitting a database.
3. Low Latency: By connecting directly to the WebSocket trusted port, you bypass the entire HTTP
layer, authentication middleware of the web app, and headers. It is a raw TCP pipe transmitting JSON.

Advanced Idea: "The Hive Mind"
Since PHP processes are cheap, you don't need to run 1 script per NPC. You can run one script that
manages an array of 50 NPCBrain objects.

1. Server sends World State for 50 NPCs in one big JSON packet.
2. The PHP CLI loops through $this->brains[$npcId]->think().
3. PHP sends back a bulk packet of 50 commands.

Would you like me to adjust the script to handle a "Swarm" of brains instead of just one?

*/





