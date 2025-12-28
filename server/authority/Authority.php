<?php

namespace Cartographica\Authority;

/*
To make your NPCs "Pro Players," the Fitness Calculator must act as a sophisticated scout, grading
NPCs not just on whether they are alive, but on how effectively they contribute to the "Win State"
of their faction.

This logic lives on the Authority Server because it has the "Ground Truth" (it knows if a unit
actually hit a target or if a resource was actually deposited).

The Multi-Objective Fitness Function
We don't just want one number; we want a weighted sum of behaviors. This allows you to tune the
"Meta" of your game. If you want more aggressive NPCs, you increase the combat weight in your config.
*/

class FitnessCalculator {
    // Weights can be adjusted via your game config
    private array $weights = [
        'survival'   => 0.1,  // Reward per tick stayed alive
        'efficiency' => 1.5,  // Reward per resource gathered
        'aggression' => 2.0,  // Reward per damage dealt
        'altruism'   => 2.5,  // Reward for healing/protecting others
        'exploration'=> 0.5   // Reward for uncovering fog of war
    ];

    /**
     * Calculates the "Reward Delta" for a single tick
     */
    public function calculateTickReward(array $prevStats, array $currentStats): float {
        $reward = 0.0;

        // 1. Economic Efficiency
        if ($currentStats['gold'] > $prevStats['gold']) {
            $reward += ($currentStats['gold'] - $prevStats['gold']) * $this->weights['efficiency'];
        }

        // 2. Combat Performance
        if ($currentStats['damage_dealt'] > $prevStats['damage_dealt']) {
            $reward += ($currentStats['damage_dealt'] - $prevStats['damage_dealt']) * $this->weights['aggression'];
        }

        // 3. Pain Penalty (Negative Reinforcement)
        if ($currentStats['hp'] < $prevStats['hp']) {
            $damageTaken = $prevStats['hp'] - $currentStats['hp'];
            $reward -= ($damageTaken * 1.2); // Penalty is slightly higher than aggression reward to favor survival
        }

        // 4. Strategic Positioning (Social/Team)
        if ($currentStats['is_near_ally'] && $currentStats['hp'] < 50) {
            $reward += $this->weights['altruism'] * 0.1; // Small reward for seeking help
        }

        return $reward;
    }
}


/*
The "Intelligence Rating" (Cumulative Fitness)
To decide which brains to keep during a restart or an evolution cycle, the Authority tracks a
Cumulative Fitness Score (CFS).

// Inside your Authority Database/Registry
$npc->cfs += $tickReward;

// Over time, a "Smart" NPC will have a CFS of +5000, 
// while a "Dumb" NPC that keeps walking into walls will have -200.



How they "Learn to Play" (The Evolution Loop)
Once your NPCs have been running for a few hours, the Authority Server identifies the Alpha Agents.
1. Identify the Elites: The Authority finds the top 5% of units by CFS.
2. Snapshot the DNA: Use the AIController to request the serialized weights of these elites.
3. Broadcast Success: When a new NPC is spawned, instead of a random brain, the Authority sends an
ADMIN command to the AI Module: { "action": "CLONE_ELITE", "from_id": "elite_warrior_01", "to_id":
"new_recruit_99" }
4. Iterative Improvement: The new_recruit_99 starts with the elite's brain but continues to mutate.
If it performs even better, it becomes the new "Elite."



Handling the MMO "Meta"
The coolest part of this system is that it adapts to your players.
- If players start using a specific "kite" tactic, the NPCs will initially lose fitness (die).
- Their Death Analysis (the "Autopsy" we built) will flag that area/tactic as a hazard.
- New generations will mutate weights that happen to counter that tactic.
- Result: Your players will notice that "The NPCs are actually getting harder to beat today."



*/

