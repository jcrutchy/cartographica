<?php

namespace Cartographica\Authority;

/*
This PHP Client Library will act as the "Remote Control" on your Authority Server. Since your
authority server likely handles the main game loop, this library provides a clean, object-oriented
way to inject commands into the NPC module without writing raw socket strings every time.
The AI Controller Client (AIController.php)
This class handles the connection and provides methods for both game-state updates and administrative
overrides.
*/

class AIController {
    private $socket;
    private string $socketPath;

    public function __construct(string $socketPath = '/tmp/cartographica.sock') {
        $this->socketPath = $socketPath;
    }

    private function connect() {
        if (!$this->socket || !is_resource($this->socket)) {
            $this->socket = stream_socket_client("unix://{$this->socketPath}", $errno, $errstr);
            if (!$this->socket) throw new \Exception("AI Module Offline: $errstr");
        }
    }

    /**
     * Send standard game tick data
     */
    public function sendTick(array $unitData): ?array {
        return $this->transmit(array_merge(['type' => 'TICK'], $unitData));
    }

    /**
     * Admin: Update Global Archetype DNA
     */
    public function updateArchetype(string $type, string $trait, float $value): ?array {
        return $this->transmit([
            'type'   => 'ADMIN',
            'action' => 'SET_TRAIT',
            'target' => $type,
            'key'    => $trait,
            'value'  => $value
        ]);
    }

    /**
     * Admin: Create a new Unit Type dynamically
     */
    public function defineUnitType(string $name, array $config): ?array {
        return $this->transmit([
            'type'   => 'ADMIN',
            'action' => 'ADD_ARCHETYPE',
            'name'   => $name,
            'data'   => $config
        ]);
    }

    /**
     * Admin: Tweak a specific live NPC (Buffs/Debuffs)
     */
    public function modifyInstance(string $unitId, string $trait, float $value): ?array {
        return $this->transmit([
            'type'    => 'ADMIN',
            'action'  => 'MODIFY_INSTANCE',
            'unit_id' => $unitId,
            'key'     => $trait,
            'value'   => $value
        ]);
    }

    private function transmit(array $packet): ?array {
        $this->connect();
        fwrite($this->socket, json_encode($packet) . PHP_EOL);
        
        // Wait for response (Blocking read for Admin commands to ensure sync)
        $response = fgets($this->socket);
        return $response ? json_decode($response, true) : null;
    }



/*
Admin Tools for Multi-Module Management
Since you have multiple AI instances, the Authority Server needs to be able to broadcast changes to all of them at once.
*/
    // In AIController.php (Authority Side)
    public function broadcastGlobalPolicy(string $trait, float $value) {
        // Instead of one socket, send to the Message Broker (Redis/RabbitMQ)
        $this->broker->publish("ai_admin_broadcast", [
            'action' => 'SET_TRAIT',
            'key' => $trait,
            'value' => $value
        ]);
    }




}








$ai = new AIController();

// --- SCENARIO 1: Global Rebalance ---
// Scouts are too brave? Tone them down globally.
$ai->updateArchetype('scout', 'caution', 0.95);

// --- SCENARIO 2: Dynamic Event ---
// A "Berserker Curse" hits a specific warrior instance
$ai->modifyInstance('warrior_unit_882', 'aggressiveness', 1.0);
$ai->modifyInstance('warrior_unit_882', 'caution', 0.0);

// --- SCENARIO 3: Standard Tick ---
// The Authority server runs its loop and feeds the AI
foreach ($activeNpcs as $npc) {
    $aiResponse = $ai->sendTick([
        'id'        => $npc->uuid,
        'team_id'   => $npc->factionId,
        'type'      => $npc->type,
        'hp'        => $npc->currentHp,
        'x'         => $npc->posX,
        'y'         => $npc->posY,
        'last_reward' => $npc->calculateLastActionFitness()
    ]);

    // Apply the AI's neural decision to the game world
    if ($aiResponse) {
        $npc->applyForceVector($aiResponse['vector']);
    }
}





/*

Integrated Architecture Summary

Component             Responsibility                              Persistence
Authority Server      Physics, Health, World Rules, Rewards.      Database (MySQL/Redis)
AI Module (PHP CLI)   Neural Inference, Coordination, Memory.     JSON Files (Brains/Teams)
IPC Bridge            Low-latency UDS Socket communication.       None (Streaming)
Admin Protocol        Live DNA updates, Archetype injection.      archetypes.json



Closing the Loop: "The Evolution Cycle"

With this client, your Authority server can even become its own "Scientist." You can write a script that:
1. Monitors which NPCs are living longest.
2. Queries their traits via the AIController.
3. Automatically applies the "winning" traits to the base archetypes.json every midnight.
This creates a self-optimizing MMO where the NPCs actually get smarter the longer your game runs.



*/



