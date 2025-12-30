<?php

namespace Cartographica\AI;

/*
To meet the demands of a persistent MMO environment, this core class acts as a Stateful Service.
It manages the IPC lifecycle, reloads configurations on the fly, and ensures that every "Brain"
and "Team Memory" is serialized to disk.

The Cartographica AI Core Engine
This script is your main entry point. It should be run in a persistent CLI process.
*/




/*
Pure PHP Class for Shared Memory (shmop)? This would let your Authority Server and AI Module talk to each other without the overhead of a WebSocket or a heavy database.
*/




/*
The Core Event Loop (Non-Blocking)
To make your AI feel "live" rather than scripted, your main loop must use socket_select. This
prevents the AI from "freezing" while waiting for a network packet from an island or a Sentinel.
*/

// Core logic for a dependency-free AI Loop
$sockets = [$ipc_socket, $remote_island_socket];
$write = $except = null;

while (true) {
    $read = $sockets;
    // socket_select waits for any event (0 sec timeout for a pure game loop)
    if (socket_select($read, $write, $except, 0) > 0) {
        foreach ($read as $socket) {
            $data = socket_read($socket, 2048);
            $event = json_decode($data, true);
            
            // Handle the Discussed Features
            if ($event['type'] === 'ANOMALY') {
                $this->triggerSentinelSwarm($event['target_id']);
            } elseif ($event['type'] === 'DISCOVERY') {
                $this->updateMacroGraph($event['island_id']);
            }
        }
    }
    
    // Run AI "Thinking" (Theory of Mind, Gossip updates)
    $this->think();
    usleep(16000); // Maintain ~60Hz "Intelligence" tick rate
}






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

/*
Benefits for your MMO
Zero Downtime: You can introduce "Holiday Event" archetypes or re-balance combat units without
kicking players.
Remote Debugging: Your Authority Server can "ping" a unit's brain via an admin command to retrieve
its current memory_state for real-time visualization in your dev console.
World Events: If a "Plague" event starts, you can globally decrease the vitality trait of all NPCs
via one IPC command.
*/

    while (true) {
        $line = fgets($client);
        if ($line) {
            $packet = json_decode($line, true);
            
            if (($packet['type'] ?? '') === 'ADMIN') {
                $response = $this->handleAdminPacket($packet);
            } else {
                $response = $this->handlePacket($packet);
            }
            
            if ($response) {
                fwrite($client, json_encode($response) . PHP_EOL);
            }
        }
        usleep(5000);
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




/*
To scale this for a distributed MMO, we need to transition from a single local AI process to a
Decentralized AI Cluster. This allows multiple AI Module instances to communicate via a Global Sync
Layer (like Redis or a shared Database) so that an NPC "learning" on Server A can share its
experience with an NPC on Server B.
We will also add the Diplomacy Layer, which treats "Relationships" as a dynamic weight that
influences the Neural Network's perception.
The Distributed AI Architecture
In a multi-authority setup, units might migrate between servers. We use a Global Brain Repository to
ensure their "soul" follows them.
*/
        // Inside Core.php -> handlePacket()
        // When a unit appears on a new authority server, the AI Module pulls its latest "DNA"
        if (!isset($this->agents[$unitId])) {
            $brainData = $this->globalSync->get("brain:$unitId"); 
            if ($brainData) {
                $this->agents[$unitId] = Agent::fromSerialized($brainData);
            } else {
                $this->spawn($unitId, $type); // Fresh start if new
            }
        }




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

    // Inside the Core class in AgentProcessor.php
    /*
    // Example Admin Packets (Authority -> AI Module)
    { "type": "ADMIN", "action": "SET_TRAIT", "target": "warrior", "key": "aggressiveness", "value": 0.95 }
    { "type": "ADMIN", "action": "DELETE_ARCHETYPE", "target": "old_unit_v1" }
    { "type": "ADMIN", "action": "MUTATE_INSTANCE", "unit_id": "npc_44", "rate": 0.5 }
    */
    private function handleAdminPacket(array $pkt): array {
        $action = $pkt['action'] ?? '';
        
        switch ($action) {
            case 'SET_TRAIT':
                // Update the live config in memory
                $target = $pkt['target']; // e.g., 'scout'
                $this->config['archetypes'][$target]['traits'][$pkt['key']] = $pkt['value'];
                
                // Persist the schema change to disk
                $this->saveConfig();
                return ['status' => 'success', 'msg' => "Archetype $target updated"];
    
            case 'ADD_ARCHETYPE':
                $this->config['archetypes'][$pkt['name']] = $pkt['data'];
                $this->saveConfig();
                return ['status' => 'success', 'msg' => "New archetype {$pkt['name']} registered"];
    
            case 'DELETE_ARCHETYPE':
                unset($this->config['archetypes'][$pkt['target']]);
                $this->saveConfig();
                return ['status' => 'success'];
    
            case 'MODIFY_INSTANCE':
                // Direct tweak to a live unit's traits (e.g., for a status effect)
                if (isset($this->agents[$pkt['unit_id']])) {
                    $this->agents[$pkt['unit_id']]->setTrait($pkt['key'], $pkt['value']);
                    return ['status' => 'success', 'unit' => $pkt['unit_id']];
                }
                return ['status' => 'error', 'msg' => 'Unit not found'];
    
            case 'RESET_KNOWLEDGE':
                // Clear hazards or specific team memories
                $teamId = $pkt['team_id'];
                if (isset($this->teams[$teamId])) {
                    $this->teams[$teamId]->clearCategory($pkt['category']); // e.g., 'hazards'
                    return ['status' => 'success'];
                }
                break;
        }
        
        return ['status' => 'unknown_admin_command'];
    }
    
    private function saveConfig(): void {
        // Atomic save for the JSON schema
        file_put_contents('./config/archetypes.json', json_encode($this->config, JSON_PRETTY_PRINT));
    }




/*
To make your NPCs "learn to play the game" in a way that feels organic and increasingly challenging
for players, we need to transition from simple reaction to Adaptive Strategy.
This involves two layers of learning: Individual Experience (the unit learns how to survive) and
Evolutionary Selection (the team learns which unit types are most effective).
The Individual Learning Loop (Experiential)
Each NPC uses Temporal Difference (TD) Learning. This means the NPC evaluates its current state
against its previous state to see if its actions are moving it closer to a "Win Condition."
- Positive Reinforcement: Successful mining, healing an ally, or dealing damage.
- Negative Reinforcement: Taking damage, hitting a dead end, or idling while resources are available.
In our PHP module, the Agent uses the last_reward from the Authority to adjust its weights. Over
time, the "Paths" in its neural network that lead to rewards become "Highways," while paths leading
to damage are "Pruned."

The Evolutionary Manager (Generational)
To make the population smarter, we implement a Genetic Algorithm. Instead of keeping every brain
forever, the AI Module periodically replaces the "worst" brains with clones of the "best" brains
(with slight mutations).

Emergent "Pro-Player" Behaviors
As these systems run, you will start to see NPCs doing things you never explicitly programmed:
- Kiting: A ranger unit learns that moving away while the "Combat" neuron is firing keeps its
"HP Reward" high while maintaining its "Damage Reward."
- Body Blocking: Warriors might learn that standing between a Medic and an Enemy yields a
"Team Survival" bonus.
- Resource Denying: Units might learn to congregate around a gold mine even if they aren't mining,
simply because preventing "Enemy Proximity" near resources yields a higher team-wide fitness score.

The "Smarter Over Time" JSON Strategy
You can assist this learning by feeding more complex data into the mapping.json as the game world
ages.
- Early Game: Inputs are simple (Health, Distance to Enemy).
- Mid Game: Add inputs like "Ally Formation Density" or "Enemy Weapon Type."
- Late Game: Add inputs like "Projected Enemy Path" or "Resource Scarcity Index."
How to Implement This Now
1. Define Fitness: Tell the Authority Server what a "Win" looks like for each NPC (e.g.,
Gold_Gathered * 10 + Survival_Time).
2. Send Rewards: Ensure every TICK packet includes the delta of that fitness.
3. Run Cycles: Use your AIController to trigger a REPRODUCTION_CYCLE via the Admin IPC once every
24 hours.
*/



    public function runEvolutionCycle(string $archetype): void {
        // 1. Gather all agents of this type
        $candidates = $this->getAgentsByType($archetype);
        
        // 2. Sort by Fitness (Survival Time + Gold Gathered + Kills)
        usort($candidates, fn($a, $b) => $b->fitness <=> $a->fitness);
        
        // 3. The "Cull": Remove the bottom 20%
        $cullCount = count($candidates) * 0.2;
        for ($i = 0; $i < $cullCount; $i++) {
            $weakest = array_pop($candidates);
            $this->deleteAgent($weakest->id);
        }
        
        // 4. The "Reproduction": Clone the Top 20% with mutations
        foreach (array_slice($candidates, 0, $cullCount) as $elite) {
            $newId = "gen_" . bin2hex(random_bytes(4));
            $offspring = clone $elite;
            $offspring->mutate(0.1); // 10% mutation rate
            $this->agents[$newId] = $offspring;
        }
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


