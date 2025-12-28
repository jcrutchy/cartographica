<?php

namespace Cartographica\AI;

/*
The Pattern Recognition Neural Layer (The "Frame-Check")
To outsmart the hackers, this sub-network focuses on Input Periodicity. Humans are chaotic; scripts are mathematical.
*/

class FrameCheckLayer {
    /**
     * Checks if the time between actions (ms) is too consistent.
     */
    public function analyzeRhythm(array $actionTimestamps): float {
        $intervals = [];
        for ($i = 1; $i < count($actionTimestamps); $i++) {
            $intervals[] = $actionTimestamps[$i] - $actionTimestamps[$i-1];
        }

        // Standard Deviation: Humans have a high SD (variable timing)
        // Bots have a near-zero SD (perfect timing)
        $variance = $this->calculateVariance($intervals);
        
        return ($variance < 2.0) ? 1.0 : 0.0; // 1.0 = Highly suspicious
    }
}

/*

Diplomacy: "The Citizen's Arrest"
Because we have a Diplomacy Layer, we can make it so that if a human player helps a "Vigilant" NPC
catch a hacker, their Faction Trust goes up significantly. This encourages a "Community Watch" feel
rather than a "State Surveillance" feel.

The Result:
A hacker sees a peaceful town of miners and thinks, "I'm safe to hack here; there are no Sentinels."
They activate their "Auto-Clicker."
- Instantly: The miners' passive layers detect the 0ms variance in clicks.
- The "Wanted Poster" is synced across the distributed AI modules.
- A "Sentinel Swarm" (Public units) is spawned or diverted from a nearby region.
- The Hacker is surrounded by a terrifying Matrix-style swarm before they can even teleport away.





Integrating the "City-State" reward model with your "Silent Sentinel" NPCs creates a powerful social
loop. It moves the game away from "AI as a tool" toward "AI as a neighbor." When players defend an
NPC from a hacker or help a merchant group reach a goal, they aren't just farming points—they are
securing the suzerainty of that community.

The Suzerainty & Suitor Model
Just like in Civ, players can compete for the "favor" of a specific NPC village or faction. This
is managed through a Suzerainty Vector in your Diplomacy Layer.

Trust Tier    Perception                 Automated Rewards
Neutral       Passive Observation        Basic trading.
Friendly      Silent Reporting active    NPCs share "Local Rumors" (Map reveals).
Allied        Active Defense             NPCs gift surplus resources (Gold, Wood).
Suzerain      Swarm Protection           The NPC village provides a "Hero Unit" to follow you or a
                                         global buff (e.g., +10% Speed in their territory).





*/
