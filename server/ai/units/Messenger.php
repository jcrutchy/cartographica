<?php
namespace Cartographica\AI\Units;

/*
The Messenger Archetype
This NPC carries "Discovery Packets" physically between islands, ensuring information isn't instant.

The "Social" Event: NPC Messengers
To avoid "Instant Knowledge" (which feels robotic), you can spawn Messenger NPCs. Instead of a global
Redis broadcast, the Authority emits an event that spawns a Messenger.
Event: "Village A discovered Iron Working."
Action: A Messenger NPC is spawned and given a Macro-Path to Village B.
Intelligence: Only when the Messenger reaches Village B does the AI Module update Village B's trading
weights. This creates a realistic "Fog of Information" that players can intercept.

Implementation Strategy
1. Macro Navigation: Use the findMacroPath in the AI Module to navigate the "Island Graph" before
switching to local tile pathfinding.
2. Sentinel Lockdown: When the Authority Server emits a SENTINEL_TARGET event, the AI Module
instantly converts nearby civilian NPCs into Vigilant nodes to monitor the target.
3. Social Reciprocity: Use the world_events stream to update local "Gossip" databases. If a player is
flagged as a thief in one module, the messenger NPCs will eventually carry that news to other
islands, raising prices or triggering a "City-State" trade embargo.

Scalable AI patterns for distributed multiplayer
https://www.youtube.com/watch?v=Ndixa64p3dQ

This architecture ensures that your NPCs learn and evolve together across your entire infinite graph.
TODO: Refine the "Messenger Delay" logic to calculate how fast "Gossip" travels based on the distance
between island nodes.

This video explains how to distribute authority and synchronization across multiple clients and
servers, which is foundational for your multi-module AI system.
https://www.youtube.com/watch?v=Ndixa64p3dQ

*/
class MessengerNPC extends BaseNPC {
    private $knowledgePayload;
    private array $storedKnowledge = [];

    public function deliverKnowledge(string $targetIslandId): void {
        // 1. Get Macro path to target island
        $path = $this->core->getMacroPath($this->currentIsland, $targetIslandId);
        
        // 2. Physical travel (This can be intercepted by players!)
        foreach ($path as $island) {
            $this->moveTo($island);
            if ($this->isAmbushed()) return;
        }

        // 3. Arrival: Sync local AI Module with the new news
        $this->syncLocalKnowledge();

    public function carryDiscovery(array $data, $targetVillage) {
        $this->knowledgePayload = $data;
        $this->setGoal("TRAVEL_TO", $targetVillage);
    }

    public function onArrival() {
        // Physically deliver the event to the local AI Module
        $this->broadcastLocalKnowledge($this->knowledgePayload);
    }

    }
}
