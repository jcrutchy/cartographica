<?php

namespace Cartographica\AI;

/*
To make Cartographica truly feel like a living ecosystem, we need a Global Event Bus. In a distributed system, an "Event" is the connective tissue that allows a discovery on one Authority Server to trigger a reaction across the entire network.

We will implement this using a Pub/Sub (Publish/Subscribe) pattern. When an event occurs, it is "Published" to a central broker (like Redis), and "Subscribed" modules (AI instances, Logging, or Player Clients) react based on their proximity and relevance.

1. The Event Packet Structure
Every event needs a standardized schema so that the Sentinels, the City-States, and the Authority Servers all speak the same language.

{
  "event_type": "TECH_DISCOVERY",
  "source_id": "npc_village_gamma",
  "location": {"island_id": "island_402", "x": 12, "y": 88},
  "payload": {
    "tech_name": "Advanced Masonry",
    "benefit": "Bridge durability +20%",
    "global_broadcast": true
  },
  "urgency": "LOW"
}

3. Handling Specific Event Types
Here is how your system should "Intelligence-process" these specific triggers:

A. Discovery Events (Tech/Islands)
When an NPC discovers a new island or technology, it shouldn't just be a stat change. It should trigger Social Change.
- NPC Reaction: "Expert" NPCs might migrate to the new island.
- Player Interaction: NPCs might send a "Messenger" to their human Suzerain to share the tech, strengthening the bond.

B. The "Call for Help" (Combat/Resource)
This is where the Swarm Intelligence shines.
- Attack Event: If an NPC is attacked, it publishes a HELP_NEEDED event.
- The Swarm Response: Nearby Sentinels calculate the distance. If they can reach the location within 30 seconds, they shift to Interception mode.
- Resource Discovery: If a lone scout finds a gold vein, it publishes a RESOURCE_FOUND event. The village "Unit Coordinator" then reassigns 5 miners from a depleted island to the new one.

C. Sentinel & Security Events (Hacker Identification)
- The Silent Flag: When a Sentinel identifies a hacker, it publishes an ANOMALY_DETECTED event with a High Urgency.
- The Lockdown: This event can trigger the Authority Server to temporarily "freeze" the suspected player's ability to trade or use portals while the AI continues to gather evidence.

4. Event-Driven Diplomacy (The "Gossip" Engine)
Events are the fuel for your NPCs' memories.

Event                     Social Consequence                    Global Sync Impact
Player Saves NPC          "Hero" tag added to Player.           Neighboring islands increase Trust by 10%.
Tech Discovered           "Industrial Age" state triggered.     Trade prices for raw materials drop globally.
Griefing Detected         "Bounty" published.                   NPCs refuse to talk to the player (Social Blacklist).


*/

/*
The Core Event Dispatcher (EventBus.php)
This lives in your AI Module and acts as the "Ear" of the NPC.
*/

class EventBus {
    public function publish(string $type, array $data, string $urgency = 'LOW') {
        $packet = [
            'type' => $type,
            'timestamp' => microtime(true),
            'data' => $data,
            'urgency' => $urgency
        ];
        // Send to Redis/Message Broker for all AI instances to see
        $this->broker->publish('global_events', json_encode($packet));
    }

    public function subscribeToThreats(callable $callback) {
        $this->broker->subscribe(['hacker_alerts', 'combat_calls'], $callback);
    }
}
