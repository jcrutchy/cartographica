<?php
namespace Cartographica\Authority;
use Cartographica\Core\RedisBus;

/*
Authority Server (the physical game logic)
*/

class WorldAuthority {
    private RedisBus $bus;

    public function __construct() {
        $this->bus = new RedisBus();
    }

    /**
     * Called whenever a physical event occurs on this server
     */
    public function onWorldUpdate(string $type, array $meta): void {
        // 1. Check for Cheating/Anomalies (e.g., speed, teleport, jitter)
        if ($this->isAnomalous($meta)) {
            $this->bus->publish('security_stream', [
                'event' => 'ANOMALY_DETECTED',
                'target_id' => $meta['actor_id'],
                'signature' => $meta['input_pattern'],
                'coords' => $meta['coords']
            ]);
        }

        // 2. Publish Standard Discoveries (Island, Tech, Resources)
        $this->bus->publish('discovery_stream', [
            'event' => $type,
            'source_id' => $meta['actor_id'],
            'payload' => $meta['data']
        ]);
    }

    private function isAnomalous(array $data): bool {
        // Frame-Perfect Detection: If variance in input is near zero, it's a bot
        return isset($data['input_variance']) && $data['input_variance'] < 0.0001;
    }

    /*
     * Triggered when a player or NPC performs an action
       This handles the "Physical Truth"—detecting discoveries and monitoring for hackers.
     */

/*

To build a truly intelligent, distributed system, we need to bridge the Authority Server (the physical
game engine) and the AI Module (the neural engine) with a shared Global Event Bus.

This architecture uses a Publish/Subscribe (Pub/Sub) model. When a Sentinel identifies a hacker or a
scout finds an island, they "Publish" a message to a central broker (like Redis). Any AI instance,
anywhere in your infinite graph, can "Subscribe" to these events and react in real-time.

{
The Global Event Packet Schema
Standardizing how we describe the world allows a "Small Village" in Module A to understand a "High Tech Discovery" from Module B.
  "event": "TECH_DISCOVERY",
  "origin": "authority_server_01",
  "subject": {"id": "village_42", "type": "npc_faction"},
  "payload": {
    "tech_id": "advanced_metallurgy",
    "impact": {"aggression": +0.05, "trade_value": +0.15}
  },
  "broadcast": "GLOBAL" 
}

*/
    public function onWorldEvent(string $type, array $details) {
        // Physical verification of discovery or tech
        $this->bus->publish('world_events', [
            'type' => $type,
            'data' => $details,
            'timestamp' => microtime(true)
        ]);
        
        // Anti-Cheat: Frame-perfect click detection
        if ($this->detectInputAnomaly($details)) {
            $this->bus->publish('security_alerts', [
                'type' => 'SENTINEL_TARGET',
                'id' => $details['player_id'],
                'signature' => $details['input_pattern']
            ]);
        }
    }

    private function detectInputAnomaly($data) {
        // Logic to detect 0ms variance in clicks
        return (isset($data['variance']) && $data['variance'] < 0.001);
    }

}





/**
 * Fitness Function Logic
 * Triggered by the Authority Server after an action is executed


transition from "Simple Reinforcement" to Collective Intelligence. We will achieve this by adding a
Death Analysis system to the Blackboard and a Global Objective Override to the Decision Processor.

The Fitness Function (The "Reward" Logic)
The fitness function lives on your Game Authority server. It calculates a "Reward Score" which is
sent back over IPC.


 */
public function calculateReward(array $unitState, array $actionTaken): float {
    $reward = 0.0;

    // 1. Survival & Social Synergy Reward
    if ($unitState['hp_recovered'] > 0) {
        $reward += 2.0; // Big bonus for getting healed
        if ($unitState['near_medic']) $reward += 1.0; // Bonus for proximity logic
    }

    // 2. Efficiency Reward (Mining)
    if ($actionTaken['type'] === 'GATHER' && $unitState['payload_increased']) {
        $reward += 0.5;
    }

    // 3. Negative Reinforcement (The "Pain" Signal)
    if ($unitState['damage_taken'] > 0) {
        $reward -= 1.5; 
    }

    return $reward;
}







/*
The Authority Server Broadcast
Your Authority Server (the MMO engine) receives this and broadcasts it to all nearby players as a
"vocal" or "thought" event.
*/

// In your Authority Server loop
$aiResponse = $aiClient->sendTick($npcData);

if (isset($aiResponse['meta'])) {
    $this->broadcastToNearbyPlayers($npc->pos, [
        'type' => 'NPC_THOUGHT',
        'source_id' => $npc->id,
        'text' => $aiResponse['meta']['intent'] . " because " . $aiResponse['meta']['reason'],
        'confidence' => $aiResponse['meta']['confidence']
    ]);
}







/*
The "Gifting" Protocol (City-State Style)
When your Authority Server calculates a high Cumulative Fitness Score (CFS) for a player-NPC
interaction, it triggers a GRANT_REWARD command back through the IPC.
*/

// Inside Authority Server Logic
if ($player->reputation['mining_village'] > 1000) {
    // If the village is thriving due to player protection/help
    $reward = [
        'type' => 'UNIT_GRANT', 
        'unit_type' => 'expert_miner',
        'reason' => 'Suzerainty Milestone reached'
    ];
    $aiClient->sendAdminCommand($reward);
}

/*
Community Building through "Silent Defense"
The "mistrust" issue is solved by making the AI's "Sentinel" actions feel like a Civic Service.
If a veteran player bullies a newbie near a "Friendly" village:
1. The Silent Report: The village NPCs observe the bullying and drop the veteran's Trust to Hostile.
2. The Community Bounty: Instead of just attacking, the village "Crowdfunds" a bounty.
3. The Call to Arms: The NPCs send a message to their Suzerain (a high-level human ally): "Our
village is under threat. Can you help?" This builds a bond: the NPCs provide the "Intel" and
"Rewards," while the Human provides the "Muscle."



The "Undercover" Sentinel Logic
To keep the world feeling organic, use Functional Camouflage. The AI doesn't need to look like a
robot to catch a hacker.

- The Merchant Node: While trading, the merchant calculates the player's "Response Latency."
  If it’s too fast, the merchant "stutters" in dialogue—a silent signal that it is recording a
  pattern.

- The Traveling Unit: A group of NPCs moving between cities acts as a Mobile Sentinel Swarm.
  If they see a speed-hacker, they don't break character; they simply "log the glitch" to the
  Global Sync so the Public Sentinels at the destination are ready.

Multi-Module Trust Sync
Since your modules are distributed, the Trust Matrix must be global. If a player is a hero in the
"Northern Mining Village" (Module A), the "Southern Trade Hub" (Module B) should hear stories of
their deeds via the Global Knowledge Store.

NPC Thought Bubble: "I've heard you protected the miners in the North.
Here is a discount on my finest iron."

Strategic NPC AI systems
https://www.youtube.com/watch?v=gVopgC_hGz8



*/




