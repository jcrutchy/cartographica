<?php

namespace Cartographica\AI;

/*

The Clean IPC Module Manager
This module loads the JSON config and manages the connection to your IPC socket.

*/

class NPCModuleManager {
    private array $activeAgents = [];
    private array $config;
    private $ipcSocket;

    public function __construct(string $configPath) {
        $this->config = json_decode(file_get_contents($configPath), true);
    }

    public function connect(string $path) {
        $this->ipcSocket = stream_socket_client("unix://$path", $errno, $errstr);
        stream_set_blocking($this->ipcSocket, false);
    }

    public function spawn(string $id, string $type) {
        if (!isset($this->config[$type])) throw new \Exception("Unknown Archetype: $type");
        
        $c = $this->config[$type];
        // Note: input count in JSON must equal (World Inputs + Output Count) for memory feedback
        $this->activeAgents[$id] = new Agent($c['inputs'], $c['outputs'], $c['traits']);
    }

    public function loop() {
        while (true) {
            if ($data = fgets($this->ipcSocket)) {
                $packet = json_decode($data, true);
                $id = $packet['id'];

                if (!isset($this->activeAgents[$id])) {
                    $this->spawn($id, $packet['type'] ?? 'scout');
                }

                $agent = $this->activeAgents[$id];

                // 1. Process Learning
                if (isset($packet['reward'])) {
                    if ($packet['reward'] < 0) $agent->mutate();
                }

                // 2. Perception (Define your world-to-brain mapping here)
                $worldInputs = [
                    $packet['hp'] / 100,
                    $packet['enemy_proximity'],
                    $packet['resource_proximity']
                ];

                // 3. Think & Reply
                $actionVector = $agent->think($worldInputs);
                fwrite($this->ipcSocket, json_encode([
                    'id' => $id,
                    'vector' => $actionVector
                ]) . PHP_EOL);
            }
            usleep(10000); // 100hz max processing
        }
    }
}



/*

Why this is powerful for your Game Authority:
Persistent Personalities: Because the memory buffer is part of the Agent object, if an NPC is
"chasing" a player, it will continue chasing even if the player briefly dips behind cover, because
its "Move" neurons are still firing from the previous tick's memory.

Trait Modularity: You can add a greed trait to the JSON. In your perception logic, just multiply
gold_value by $agent->traits['greed']. The agent will naturally prioritize gold.

JSON Hot-Swapping: You could write a script that updates archetypes.json based on player feedback,
and the PHP module can file_get_contents again to update the "DNA" for all newly spawned units.

*/





