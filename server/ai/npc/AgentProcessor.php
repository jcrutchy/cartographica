<?php

namespace Cartographica\AI;

/*

The Unified AI Module (AgentProcessor.php)
This ties everything together. It "wires" the inputs from the JSON mapping to the Brain.

*/

class AgentProcessor {
    private Agent $agent;
    private KnowledgeStore $teamKnowledge;
    private array $mapping;

    public function process(array $ipcPacket) {
        $inputs = [];
        $qx = $ipcPacket['x'];
        $qy = $ipcPacket['y'];

        // 1. Build the Input Vector based on JSON Mapping
        foreach ($this->mapping['inputs'] as $map) {
            if ($map['source'] === 'local') {
                $inputs[] = $ipcPacket[$map['key']] ?? 0.0;
            } elseif ($map['source'] === 'blackboard') {
                $inputs[] = $this->teamKnowledge->query($map['query'], $map['params'], $qx, $qy);
            }
        }

        // 2. Neural Inference
        $decision = $this->agent->think($inputs);

        // 3. Logic-Assisted Output
        $finalAction = $this->interpretDecision($decision, $ipcPacket, $qx, $qy);

        return $finalAction;
    }


/*
Wiring the Brain to the Coordinator
In your processTick, the unit now receives a "Job Signal." If the Coordinator assigns it to the gold
mine, the global_priority_pull input is set to 1.0. If not assigned, it stays 0.0.
*/
    public function process(array $ipcPacket) {
        $id = $ipcPacket['id'];
        $type = $ipcPacket['type'];
    
        // 1. Ask Coordinator for orders
        $assignedJob = $this->coordinator->shouldRecruit($id, $type, $ipcPacket['x'], $ipcPacket['y']);
    
        // 2. Prepare Inputs
        $inputs = $this->perceptionLayer->buildInputs($ipcPacket, $this->teamKnowledge);
    
        // 3. Inject Coordination Signal
        // If assigned to a job, force the "priority" input to 1
        $inputs['global_priority_pull'] = ($assignedJob !== null) ? 1.0 : 0.0;
    
        // 4. Brain processes the "Order"
        $vector = $this->agent->think($inputs);
    
        // 5. Output
        return $this->translateVector($vector, $assignedJob);
    }

/*
Why this structure works:
Preventing "Lemmings": If 10 miners see gold but the Coordinator only allows 3, the remaining 7 will
have a global_priority_pull of 0.0. Their Neural Nets will default to their next-best priority (like
wood or scouting).

Class-Based Danger: If a "Scout" dies to a "Turret," the KnowledgeStore can increase the danger_sense
input specifically for other "Scouts" in that area, while letting "Tanks" pass through (since they
aren't incompatible).

The "Medic!" Protocol: When a unit is wounded, the Coordinator treats "Healing" as a job. It recruits
the nearest "Medic" to that "Job Site."

Persistence Tip
I suggest saving the jobRegistry and assignments to a JSON file as well. This way, if you restart
your NPC Module, your units don't "forget" what they were working on and all wander off.
*/



    private function interpretDecision(array $vector, array $pkt, float $x, float $y): array {
        // If the 'move_to_target' neuron is the strongest
        if ($vector[0] > 0.7) {
            // Check if we are fleeing (high threat) or mining (exhausted)
            if ($pkt['current_node_empty'] > 0.5) {
                $target = $this->teamKnowledge->getNearest('mining_node', $x, $y);
                return ['cmd' => 'MOVE', 'x' => $target['x'], 'y' => $target['y']];
            }
            
            if ($pkt['hp'] < 30) {
                $safe = $this->teamKnowledge->getNearest('safe_point', $x, $y);
                return ['cmd' => 'RETREAT', 'x' => $safe['x'], 'y' => $safe['y']];
            }
        }

        return ['cmd' => 'IDLE'];
    }



/*
Global Objective: The "Gold Rush" Override
To maximize a new benefit, we use a Global Urgency Vector. If the team is low on gold and a source
is found, the Blackboard issues a "Call to Action" that adds an artificial pull to all compatible
units.
*/
    
    private function applyGlobalIncentives(array &$inputs, KnowledgeStore $ks): void {
        // Check if team is in "Resource Crisis"
        if ($ks->getGlobalMetric('gold_reserves') < 100) {
            $newGold = $ks->getNewestFact('gold_source');
            if ($newGold) {
                // We inject a '1.0' into a dedicated "Global Priority" neuron
                $inputs['global_priority_signal'] = 1.0;
            }
        }
    }


/*
Adding a "Spectator Mode" packet is a fantastic way to make your MMO feel alive. It turns the
"black box" of AI into a narrative for your players. Instead of an NPC just running away, a player
sees a "Low Health! Retreating to Healer" thought bubble, which makes the world feel inhabited by
thinking beings.

The Spectator Packet Structure
We will modify the AgentProcessor to include a meta field in the response. This field translates the
neural activation and blackboard queries into human-readable strings.

Updating the IPC Response
The AI Module now sends this extra metadata back to the Authority Server.

{
  "id": "npc_882",
  "vector": [0.1, 0.85, 0.05],
  "meta": {
    "intent": "MOVE_TO_MEDIC",
    "reason": "hp percent low",
    "confidence": "85%"
  }
}

*/
    private function generateSpectatorMeta(array $vector, array $inputs, array $mapping): array {
        $dominantOutputIndex = array_search(max($vector), $vector);
        $actionName = $mapping['outputs'][$dominantOutputIndex] ?? 'Thinking';
    
        // Determine the "Reason" by finding the strongest input signal
        $strongestInputIndex = array_search(max($inputs), $inputs);
        $reason = $mapping['inputs'][$strongestInputIndex]['name'] ?? 'Instinct';
    
        return [
            'intent' => $actionName,
            'reason' => str_replace('_', ' ', $reason),
            'confidence' => round(max($vector) * 100) . '%'
        ];
    }





/*
Implementation: The "Anti-Cheat" Perception Layer
We add a Heuristic Buffer to the AgentProcessor. This calculates the "Entropy" of a player's
movement. Humans have "noise" in their movement; bots are often too perfect.
*/
    private function calculateHumanityScore(array $telemetry): float {
        // Humans don't move in perfectly straight lines or click at exact intervals
        $jitter = $this->analyzeInputJitter($telemetry['input_history']);
        
        // If jitter is near zero, it's likely a script/hack
        return ($jitter < 0.001) ? 0.0 : 1.0; 
    }
    
    public function buildInputs($pkt, $ks) {
        $inputs = parent::buildInputs($pkt, $ks);
        
        // New Input: Target "Unnaturalness"
        // If this is 1.0, the Brain treats the target as a high-priority threat/anomaly
        $inputs['target_unnaturalness'] = 1.0 - $this->calculateHumanityScore($pkt['target_data']);
        
        return $inputs;
    }





}



/*

[examples: mining unit having run out of resource in its current mining location
automatically moving to another nearby location with same resource, or non-military units scrambling
to known safe areas, like inside a protective wall or behind a friendly force)]

How this solves your examples:
The Mining Unit: When current_node_empty becomes 1.0, that signal hits a specific neuron in the
brain. Through reinforcement (Reward/Penalty), the brain learns that when this neuron is high,
activating the move_to_target neuron leads to a Reward (more resources).

The Safe Zone Scramble: Non-military units have a high caution trait in their JSON. This amplifies
the threat_nearby input. When the Neural Net sees a threat spike, it triggers the movement neuron.
The interpretDecision logic then looks at the blackboard to find the nearest protective_wall and
provides the coordinates.

Persistence Strategy
I recommend saving the KnowledgeStore (Team Memory) every 60 seconds and saving the Agent (Individual
Brain) weights only when the unit "levels up" or "dies" (to capture its final learned state).





Strategic Response: "The Deadlock"
When a Sentinel NPC identifies a hacker, it stops playing by the "standard" rules and uses
Calculated Interruption.
1. Prediction: The AI predicts the hacker's next "Perfect Frame" move.
2. Obstruction: The UnitCoordinator sends multiple Sentinels to create a physical "Collision Box"
that bots often can't path-find around.
3. Authority Handshake: The AI tells the Authority Server: "I am 99% sure this is a bot. Enable
'Weight-Lag' for this player." The server then artificially slows the player's responses, making
their "speed hack" useless.

Multi-Module Global Intelligence
In your distributed system, when one Sentinel on Server A learns a new botting pattern (e.g., a
specific sequence of movements used to exploit a boss), it uploads that Neural Pattern Fragment to
the Global Sync.

Every Sentinel on every other server instantly receives an update: "Warning: New exploitation
pattern detected. Priority: High."

Why the AI wins:
Hackers have to hard-code their cheats. Your AI mutates. If a hacker changes their script, the
AI's "Fitness Function" (which rewards catching cheaters) will drive the Sentinels to evolve new
detection weights within hours.

Would you like me to create the "Pattern Fragment" sync logic, which allows the AI modules to
share "Wanted Posters" (neural signatures of specific hackers) across your entire network?









*/





