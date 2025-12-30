<?php

namespace Cartographica\AI;

/*
The Diplomatic Negotiator (Enforcer Mode)
The Negotiator now has a "Justice" axis. If an NPC witnesses a veteran (Level 100) repeatedly killing
a new player (Level 1) in a low-level zone, the Trust Score for that veteran drops to -1.0 (Vermin
status) across the entire faction.
*/

class DiplomaticNegotiator {
    private array $lawThresholds = [
        'griefing' => 0.8, // 80% confidence of bullying
        'speed_hack' => 0.95 // 95% confidence of teleport/speed hacking
    ];

    public function evaluateBehavior(string $subjectId, array $behaviorData): void {
        // If the AI detects a veteran hunting a newbie (Bullying)
        if ($behaviorData['level_gap'] > 50 && $behaviorData['victim_level'] < 10) {
            $this->applySanction($subjectId, 'bullying');
        }

        // Check for movement anomalies (Hacking)
        if ($behaviorData['distance_moved'] > $behaviorData['max_legal_speed']) {
            $this->applySanction($subjectId, 'hacking');
        }
    }

    private function applySanction(string $id, string $type): void {
        // 1. Drop Trust globally
        $this->knowledgeStore->updateRelation('WORLD_POLICE', $id, -1.0);
        
        // 2. Trigger Authority Action
        // We send a command back through IPC for the server to handle
        $this->ipc->sendAdminCommand([
            'action' => 'AUTHORITY_INTERVENTION',
            'target' => $id,
            'reason' => $type,
            'severity' => ($type === 'hacking' ? 'KICK_BAN' : 'BOUNTY_PLACED')
        ]);
    }
}




/*


Automatic Response: "The Swarm"
When a "Hacker" or "Bully" is flagged, the AI doesn't just watch. The Unit Coordinator can issue a
global "Intercept" order.
- For Bullying: The Coordinator recruits nearby high-level NPCs to "Guard" the new player or "Exile"
the veteran by attacking them on sight.
- For Hacking: The AI sends a packet to the Authority Server to shadow-ban or "Jail" the user while
the AI records the coordinate logs as evidence.

Distributed Verification (Cross-Module Sync)
Because your game is distributed, if a player is flagged as a hacker on AI Module Instance A, that
"Criminal Record" is published to the Global Knowledge Store.
When that player teleports to a map handled by AI Module Instance B, the NPCs there are already
"Aggressive" toward them and have their "Observation" neurons at 100% sensitivity to catch the next
teleport.

Spectator Mode for "Justice"
Using the "Thought Bubbles" we designed, other players will see the AI's logic in action:
- NPC Thought: "Detecting unfair combat... Intervening to protect citizen."
- NPC Thought: "Movement anomaly detected in Player_X. Reporting to Authority."

Key System Integration

Action       AI Module Role                        Authority Server Role
Detection    Identifies patterns (Neural Net).     Provides raw telemetry (X, Y, Speed).
Judgment     Lowers Diplomacy/Trust score.         Confirms hard-limit violations (No-clip).
Execution    Coordinates NPC "Guards."             Disconnects, bans, or jails the player.
Learning     Learns new "Cheat" patterns.          Stores the "Criminal Record" in DB.




To make the AI module outsmart hackers, we shift from "detecting rules" to "Predictive Modeling."
Hackers use automation to gain a speed or precision advantage. To beat them, your AI must learn the
Probability of Human Capability.

If a player’s actions fall outside the "Human Bell Curve" (e.g., 100% archery accuracy while moving
at max speed), the AI doesn't just report it—it adapts its combat strategy to counter "Perfect
Input" playstyles.




*/



/*
To achieve a world where players cannot distinguish between humans and NPCs, you must move beyond
"AI as a script" and treat AI as Autonomous Agents with Social Memory.

The "city-state worker theft" exploit in games like Civilization happens because the AI lacks Social
Reciprocity—it doesn't understand that a "one-off" theft is a permanent breach of a social contract.
To fix this, your AI must perceive actions not as math, but as Reputational Signals.

The Social Contract Layer (Reputation as a Resource)
In a human society, stealing a worker isn't just "losing a unit"; it’s a declaration of hostility
that triggers a social "Red Flag." We implement this using a Global Trust Decay system.


Mimicking Human "Imperfect" Behavior
To pass the "Turing Test" of gameplay, NPCs must avoid being "perfect" machines. Humans make
mistakes, get distracted, and have unique rhythms.

Reaction Latency: Never allow an NPC to react in 0ms. Add a human_delay based on the NPC's alertness
trait.

Contextual Distraction: If an NPC is mining, they shouldn't immediately notice a thief unless the
thief enters their "Perception Cone."

Emotional State: A "Angry" NPC might over-retaliate, while a "Fearful" one might pay a player to
leave them alone—exactly like a human player trying to survive a griefing attempt.

The "Unpredictable" Social Interaction
One way humans identify AI is through repetitive dialogue and predictable loops. We replace these with Dynamic Intention Bubbles.

Human Action        AI Classic Response      Your Emergent AI Response
Steal a Worker      Declare war or ignore.   Theft-Back: The AI waits until you are distracted, then steals your horse.
Gifting Resources   +10 Friendship.          Suspicion: "Why are they giving me gold? Are they planning an attack?" (Trust builds slowly).
Blocking a Path     Pathfinds around.        Communication: "You're in my way. Move, or I'll call the Sentinels."





Distributed Intelligence: The "Gossip" Network
Since your game is distributed, the Global Sync Layer (Redis) allows a "Criminal Record" to follow
the player.
1. Server A: Player steals from a merchant.
2. The Sync: The AI Module publishes: {"player": "user_123", "status": "thief", "confidence": 0.9}.
3. Server B: When the player arrives, the local NPCs don't attack—they simply raise their prices and
hide their workers. The player thinks, "Wait, how do they know?" This mimics the way human
communities blackball "toxic" players.

Interaction with Sentinels
The Sentinels fit here as the "Elite Guardians." While regular NPCs might be too weak to fight a
veteran thief, they can "Crowdfund" a Sentinel contract.
- The Transaction: The NPCs pool their resources to "Hire" a Sentinel Swarm to protect their borders
for 24 hours.
- The Deterrent: A human player sees a swarm of Sentinels hovering over a village and thinks,
"Someone must have really pissed them off. I'd better stay on my best behavior."


Why this works for Cartographica:
By giving NPCs the ability to Retaliate Proportionally and Share Information, you eliminate the
"City-State Exploits." A human player won't steal a worker because they know the social cost
(getting banned from trades, losing access to safe zones, or being hunted by Sentinels) outweighs
the unit gain.


*/



/**
 * Reputational Consequence Logic
 * If a player steals a worker, the AI evaluates the "Social Cost"
 */
public function onHarmfulAct(string $actorId, string $targetId, string $actType): void {
    $severity = $this->getSeverity($actType); // 'theft' = 0.8, 'murder' = 1.0
    
    // 1. Immediate Local Response
    $this->teams[$targetId]->setHostile($actorId);

    // 2. Social Gossip (The "Distributed Memory")
    // The news spreads through the KnowledgeStore to neighboring villages
    $this->broadcastGossip($actorId, "betrayer", $severity);

    // 3. Automated "Human-Like" Retaliation
    // Instead of just complaining, the AI recruits a "Mercenary" (another NPC) 
    // to steal something back or block the player's trade routes.
}



/*


To maximize the intelligence of Cartographica, we have to move away from "Menu-Based Diplomacy" and
"Aggro-Radiuses." In most games, diplomacy is a transaction; in Cartographica, it should be a Social
Performance.
To make interaction feel human, the AI must interpret the subtext of a player's physical actions
rather than just their dialogue choices.
1. Contextual Body Language (The "Vibe" Check)
In games like Minecraft, a unit's state is binary (Passive or Hostile). In Cartographica, we use a
Fuzzy State Machine driven by the "Theory of Mind" layer.
- Proximity Analysis: If a player approaches an NPC with a weapon drawn but pointed at the ground,
the NPC's caution rises slightly. If the weapon is raised, the NPC immediately enters
defensive_stance.
- The "Unspoken" Greeting: If a player stands still at a respectful distance, the NPC might wave
first. If the player "invades" personal space too quickly, the NPC backs away—mimicking human social
discomfort.

Eliminating the "Trade Menu" (Natural Exchange)
The most "robotic" part of NPCs is the UI. For Cartographica, we can implement Physical Barter.
- The Drop-Trade: Instead of a menu, a player drops an item on a merchant’s table.
- The Evaluation: The AI analyzes the item's value against its own current needs (e.g., if the
village is low on iron, the iron is worth more).
- The Counter-Offer: The NPC physically drops a bag of gold or another item. If the player picks it
up, the AI logs a "Successful Reciprocal Trade." If the player takes the item and their original item
back, the NPC flags them for "Attempted Theft."


Gossip-Driven Diplomacy (Reputation as a Wave)
In Civ, if you break a treaty, every leader knows instantly because of global variables. In
Cartographica, information should travel like a physical wave through the world.
- Word of Mouth: A merchant travels from Village A (where you were a hero) to Village B.
- The Update: Only after the merchant arrives and "talks" to the local NPCs (via the Knowledge Store)
does Village B change its attitude toward you.
- The "Shadow" Reputation: You might walk into a new town thinking you’re anonymous, only to find the
NPCs are whispering about your deeds in the North. This creates a "Living History" that feels earned,
not scripted.


Dynamic Group Dynamics (The "Mob" Mentality)
AI in most games acts individually. In Cartographica, NPCs should exhibit Coordinated Social
Response.
- The Bystander Effect: If one NPC is attacked, others might flee initially.
- The Rally: If a player has a high "Bully" score (from your Sentinel detection), and they attack a
beloved village elder, the NPCs’ cooperation and aggression traits spike simultaneously. They will
drop their tools and form an impromptu "Angry Mob" to drive the player out—no "Police" required.





Multi-Instance Diplomatic Consistency

Because your game is distributed across multiple AI modules, we use the Global Sync Layer to ensure
"Personality Consistency."

Event        AI Module A (Local)                      Global Sync (Redis/DB)                          AI Module B (Remote)
Betrayal     NPC "Village Elder" is killed.           Record "Player_X" as "Slayer of Elders".        All NPCs in the same Faction gain +0.5 Aggression toward Player_X.
Heroism      Player clears a dungeon for the NPCs.    Record "Player_X" as "Friend of the Forge".     Blacksmiths in the entire world offer a 5% discount.


Why this is "Unlike Any Other Game":
By removing menus and fixed scripts, you force the player to treat NPCs as Physical Entities with
Memory. If a player treats an NPC like a "Minecraft Villager" (trapping them in a box to trade), the
AI's stress trait will max out, they will stop trading, and they will broadcast a "Help/Kidnapping"
signal to the Sentinel Swarm.




*/
