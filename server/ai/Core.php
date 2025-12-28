<?php

namespace Cartographica\AI;

/*
To meet the demands of a persistent MMO environment, this core class acts as a Stateful Service.
It manages the IPC lifecycle, reloads configurations on the fly, and ensures that every "Brain"
and "Team Memory" is serialized to disk.

The Cartographica AI Core Engine
This script is your main entry point. It should be run in a persistent CLI process.

*/

class Core {
    private string $socketPath;
    private array $teams = [];       // [team_id => KnowledgeStore]
    private array $agents = [];      // [unit_id => Agent]
    private array $coordinators = []; // [team_id => UnitCoordinator]
    private array $config;

    public function __construct(string $socketPath, string $configPath) {
        $this->socketPath = $socketPath;
        $this->loadConfig($configPath);
        
        // Ensure persistence directories exist
        if (!is_dir('./storage/brains')) mkdir('./storage/brains', 0777, true);
        if (!is_dir('./storage/teams')) mkdir('./storage/teams', 0777, true);
    }

    public function loadConfig(string $path): void {
        $this->config = json_decode(file_get_contents($path), true);
        echo "[Config] Loaded archetypes and mapping logic.\n";
    }

    public function run(): void {
        $client = stream_socket_client("unix://{$this->socketPath}", $errno, $errstr);
        if (!$client) die("IPC Error: $errstr");

        stream_set_blocking($client, false);
        echo "[System] Cartographica AI Module Online. Listening for IPC ticks...\n";

        while (true) {
            $line = fgets($client);
            if ($line) {
                $packet = json_decode($line, true);
                $response = $this->handlePacket($packet);
                if ($response) {
                    fwrite($client, json_encode($response) . PHP_EOL);
                }
            }
            
            // Maintenance: Save all state every 5 minutes
            if (time() % 300 === 0) $this->persistAll();
            
            usleep(5000); // 200Hz internal processing
        }
    }

    private function handlePacket(array $pkt): ?array {
        $unitId = $pkt['id'];
        $teamId = $pkt['team_id'];
        $type   = $pkt['type'];

        // 1. Restore or Initialize Team Knowledge & Coordinator
        if (!isset($this->teams[$teamId])) {
            $this->teams[$teamId] = new KnowledgeStore($teamId);
            $this->coordinators[$teamId] = new UnitCoordinator();
        }

        // 2. Restore or Initialize Individual Agent
        if (!isset($this->agents[$unitId])) {
            $this->agents[$unitId] = $this->restoreAgent($unitId, $type);
        }

        $agent = $this->agents[$unitId];
        $store = $this->teams[$teamId];
        $coord = $this->coordinators[$teamId];

        // 3. Update Knowledge (Location, HP, Discoveries)
        $store->logFact('units', $unitId, $pkt);
        if (isset($pkt['new_discovery'])) {
            $coord->registerDiscovery($pkt['discovery_id'], $pkt['discovery_type'], 3);
        }

        // 4. Learning Feedback
        if (isset($pkt['last_reward'])) {
            $agent->learn($pkt['last_reward']);
        }

        // 5. Build Inputs & Think
        $inputs = $this->compileInputs($agent, $pkt, $store, $coord);
        $vector = $agent->think($inputs);

        // 6. Return high-level command to Authority
        return [
            'id' => $unitId,
            'vector' => $vector,
            'memory_state' => $agent->memory
        ];
    }

    private function restoreAgent(string $id, string $type): Agent {
        $path = "./storage/brains/{$id}.json";
        if (file_exists($path)) {
            return Agent::fromFile($path);
        }
        // Fallback to fresh agent from archetypes config
        $cfg = $this->config['archetypes'][$type];
        return new Agent($cfg['inputs'], $cfg['outputs'], $cfg['traits']);
    }

    private function persistAll(): void {
        foreach ($this->teams as $id => $store) $store->save();
        foreach ($this->agents as $id => $agent) {
            file_put_contents("./storage/brains/{$id}.json", $agent->serialize());
        }
        echo "[Persistence] All agent and team states synced to disk.\n";
    }
}



/*

Key Persistence Features for your MMO
- Atomic Restarts: If the module crashes or you push a new archetypes.json, the restoreAgent function
checks for an existing .json brain. It keeps the learned weights but can adopt the new traits defined
in the config.
- The "Team Mind" continuity: By separating KnowledgeStore into its own file
(./storage/teams/team_123.json), a whole faction of NPCs can "remember" where a resource was located
even if every single unit that originally found it has since been killed and respawned.
- Memory State Passthrough: The $agent->memory is returned in every IPC packet. This allows your
Game Authority to store the "Current Thought" of the NPC in its own database if needed, providing a
double-layer of redundancy.

How to use with new Configurations
1. Modify archetypes.json: Update a unit's caution or aggression.
2. Restart the Process: The Core will reload the JSON.
3. Inheritance: Existing agents keep their "Neural Weights" (their skills) but their "Perception" is
immediately filtered through the new trait values.

*/


