<?php

/*

[I already have a websocket server. it exposes a simple ipc socket server in its tick loop, so all
the npc module needs is a basic ipc socket client connection]

This makes the setup even more streamlined. Using a Unix Domain Socket (UDS) or a local TCP IPC
connection removes the entire WebSocket framing overhead (masks, headers, etc.), leaving you with a
raw, high-speed pipe.

In CLI mode, PHP’s stream_socket_client is the most performant way to handle this. It allows you to
treat the socket like a file pointer, which is extremely fast for a persistent tick loop.

The IPC NPC Module
This module is designed to sit on the same machine (or a local network) as your authority server.
It uses a "Select" pattern to handle data without blocking the neural network's processing.

*/


/**
 * Cartographica NPC Module - IPC Edition
 * Optimized for PHP 8+ CLI with JIT
 */


require 'NPCBrain.php';

// Initialize your swarm
$npcs = [
    'npc_1' => ArchetypeFactory::create('scout'),
    'npc_2' => ArchetypeFactory::create('warrior')
];

class NPCModule {
    private $socket;
    private NPCBrain $brain;
    private string $socketPath;

    public function __construct(string $socketPath = '/tmp/game_ipc.sock') {
        $this->socketPath = $socketPath;
        // 3 Inputs (Bio, Tech, Env) -> 6 Hidden -> 3 Outputs (Move, Interact, Build)
        $this->brain = new NPCBrain(3, 6, 3);
    }

    public function run() {
        // Connect to the IPC socket exposed by your game authority
        // Use 'tcp://127.0.0.1:port' if your IPC is TCP-based
        $this->socket = stream_socket_client("unix://{$this->socketPath}", $errno, $errstr, 3);

        if (!$this->socket) {
            die("IPC Connection Failed: [$errno] $errstr\n");
        }

        // Set non-blocking to ensure the NPC keeps "living" even if no data arrives
        stream_set_blocking($this->socket, false);
        
        echo "NPC Module connected to Game Authority IPC.\n";

        // LearningNPC.php adaptation:
        // Inside your NPCModule run() loop:
        while (!feof($this->socket)) {
            $start = microtime(true);

            // 1. Read tick data from Authority
            $raw = fgets($this->socket);
            if (!$raw) continue;

            if ($data) {
                $worldState = json_decode($data, true);
                //$this->processTick($worldState); (superseded)
            }

            $id = $state['id'];
            if (isset($npcs[$id])) {
                $npc = $npcs[$id];
                
                // 1. Learning (Feedback from previous tick)
                if (isset($state['reward'])) {
                    if ($state['reward'] < 0) $npc->mutate();
                }
        
                // 2. Decision
                $inputs = getPerceivedInputs($npc, $state);
                $decision = $npc->think($inputs);
        
                // 3. Output to IPC
                fwrite($ipcSocket, json_encode(['id' => $id, 'action_vector' => $decision]));
            }

            // 1. Learning Step: Apply feedback from the PREVIOUS action
            if (isset($state['last_reward'])) {
                $this->brain->learn((float)$state['last_reward']);
            }
        
            // 2. Prediction Step: Decide what to do NEXT
            $inputs = [
                $state['health'] / 100,
                $state['enemy_dist'] / 500,
                $state['resource_val'] / 10
            ];
            
            $outputs = $this->brain->think($inputs);
            $action = $this->mapOutputToAction($outputs);
        
            // 3. Send action to IPC
            fwrite($this->socket, json_encode(['action' => $action]) . PHP_EOL);

            // 4. Performance: Limit tick rate to match your server (e.g., 20 ticks/sec)
            $sleep = 50000 - ((microtime(true) - $start) * 1000000);
            if ($sleep > 0) usleep($sleep);
        }

    }

    private function processTick(array $state) {
        // Normalize your game data for the Neural Net
        // Example: Energy level, Tech progress, Enemy distance
        $inputs = [
            $state['energy'] / 100,
            $state['tech_level'] / 10,
            min(1, $state['threat_dist'] / 500)
        ];

        // The Neural "Thinking" Step
        $outputs = $this->brain->think($inputs);

        // Map highest activation to game command
        $action = $this->mapOutputToAction($outputs);

        // 3. Write back to IPC
        fwrite($this->socket, json_encode([
            'npc_id' => $state['npc_id'],
            'action' => $action,
            'timestamp' => microtime(true)
        ]) . PHP_EOL);
    }

    private function mapOutputToAction(array $outputs): string {
        $map = ['EXPLORE', 'RESEARCH', 'FORTIFY'];
        arsort($outputs);
        return $map[key($outputs)];
    }
}

// Entry Point
$module = new NPCModule('/tmp/cartographica.sock');
$module->run();


/*

Why this fits your Architecture
Low Latency: Since your WebSocket server handles the "heavy lifting" of player connections, this IPC
client acts as a high-speed "sidecar." It consumes local resources without fighting for the network
stack.

Zero-Copy Logic: By using fgets and fwrite on a local socket, the OS kernel handles the data transfer
between your server and the NPC module almost instantly.

Persistent Memory: Because the script stays alive, you can implement Heuristic Mutation. If an NPC
fails a task, you can call $this->brain->randomize() on that specific instance without restarting the
process.

A Quick Tip on Performance
If you find your IPC traffic is extremely high-volume (thousands of NPCs), avoid
json_decode / json_encode. Instead, consider pack() and unpack() to send raw binary data. This will
reduce the CPU time spent on string parsing by roughly 40-60%.

*/



/*
The Extensible Perception Layer
This is the "translation" layer. It takes your raw IPC data and filters it through the NPC's traits.
*/

function getPerceivedInputs(Agent $agent, array $worldData): array {
    $t = $agent->traits;

    // We map raw data to 4 standard input channels for the Brain
    return [
        // 1. Threat (Higher if aggressive, lower if cautious)
        ($worldData['threat_level'] ?? 0) * ($t['aggressiveness'] ?? 0.5),
        
        // 2. Opportunity (Higher if curious/expansionist)
        ($worldData['resource_density'] ?? 0) * ($t['expansionist'] ?? 0.5),
        
        // 3. Social (Proximity to allies)
        ($worldData['ally_count'] ?? 0) * ($t['sociability'] ?? 0.5),
        
        // 4. Survival (Health status vs Caution)
        (1 - ($worldData['hp'] / 100)) * ($t['caution'] ?? 0.5)
    ];
}



/*
Key Advantages:
Uniformity: The Agent doesn't care if it's a Scout or a Tank. It just processes floating-point
numbers.

Scalability: You can add 50 more traits (e.g., bravery, greed, intelligence) and simply increase the
$inputs count in the Agent constructor.

Low Latency: Using a flat array for weights and avoiding deep object nesting keeps the "thinking"
step under 1ms even in PHP.

Would you like me to add a "Recurrent Memory" slot so the NPCs can remember what they did in the
previous tick to detect patterns?
*/
