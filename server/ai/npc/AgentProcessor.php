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

*/





