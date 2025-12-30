<?php

/*

To transform your NPC from a static "thinking" machine into a Learning Agent, we need to implement a
feedback loop. In game AI, this is typically done via Reinforcement Learning (RL) or Neuroevolution.

Since backpropagation (traditional AI training) is computationally expensive in PHP's engine, the
most performant way to achieve learning in your CLI module is through Neuroevolution of Augmenting
Topologies (NEAT) principles: basically, rewarding "successful" brains and mutating "failing" ones.

The Learning Loop Logic
Prediction: Brain generates an action.

Execution: Action is sent via IPC to the Authority.

Reward: The Authority sends back a "Fitness Score" (e.g., +10 for finding gold, -50 for taking
damage).

Adaptation: If the score is low, the brain mutates its weights to try a different strategy.

*/

class LearningNPC extends NPCBrain {
    private float $fitness = 0.0;
    private float $mutationRate = 0.05; // 5% chance to change a weight

    /**
     * Tweak weights slightly based on performance
     */
    public function learn(float $reward): void {
        // Accumulate fitness
        $this->fitness += $reward;

        // If performing poorly, mutate to find a better strategy
        if ($reward < 0) {
            $this->mutate();
        }
    }

    private function mutate(): void {
        // Mutate Input -> Hidden weights
        foreach ($this->weightsInputHidden as &$row) {
            foreach ($row as &$weight) {
                if ((mt_rand() / mt_getrandmax()) < $this->mutationRate) {
                    $weight += (mt_rand(-100, 100) / 500); // Small nudge
                }
            }
        }
        
        // Mutate Hidden -> Output weights
        foreach ($this->weightsHiddenOutput as &$row) {
            foreach ($row as &$weight) {
                if ((mt_rand() / mt_getrandmax()) < $this->mutationRate) {
                    $weight += (mt_rand(-100, 100) / 500);
                }
            }
        }
    }

    public function getFitness(): float { return $this->fitness; }
}


/*

Why this is "Cool" for Cartographica:
Unique Personalities: Because each NPC starts with random weights and learns from its specific
environment, one NPC might become aggressive while another becomes a passive gatherer.

Adaptability: If you update the game balance (e.g., make enemies stronger), you don't need to rewrite
the AI code. The NPCs will "die" more often, trigger the mutate() function, and eventually learn to keep a further distance from enemies automatically.

Persistence: You can save the $brain->serialize() string to your database when the NPC "goes to
sleep" and reload it later, allowing the NPC to retain its "experience."

Performance Consideration
The mutate() function uses mt_rand(), which is faster than rand(). Since mutations only happen when a
"negative reward" occurs, the CPU impact is negligible compared to the benefits of emergent behavior.

Would you like to see how to implement a "Generation Manager" that keeps the best brains and clones
them into new NPCs?

*/



/*

[Incorporate various trait parameters, such as agressiveness, expansionist, etc (similar to basic
civilization ai) but allow for easy addition of more]

To make this both flexible and performant, we should separate Genetic Traits (hardcoded personality
biases) from the Neural Weights (learned behaviors).

By using a Trait Map, we can influence the input layer of the neural network. This allows you to add
new traits like "Greed" or "Scientific Curiosity" just by adding a key to an array, without changing
the core brain logic.

1. The Dynamic Trait System
We will use a trait-influenced input vector. Instead of the brain seeing just raw data, it sees raw
data multiplied or offset by its personality.

*/


class LearningNPC extends NPCBrain {
    // Modular trait system
    private array $traits = [
        'aggressiveness' => 0.5,
        'expansionist'   => 0.5,
        'caution'        => 0.5,
        'sociability'    => 0.5
    ];

    private float $mutationRate = 0.05;

    /**
     * Allows adding traits on the fly: $npc->setTrait('greed', 0.9);
     */
    public function setTrait(string $trait, float $value): void {
        $this->traits[$trait] = max(0, min(1, $value));
    }

    /**
     * Pre-processes world data through the lens of the NPC's traits
     */
    public function perceive(array $worldData): array {
        // Example logic: Aggressive NPCs perceive enemies as "closer" (more urgent)
        // Expansionist NPCs perceive distance to empty land as "shorter"
        
        $perceptions = [];
        
        // Input 1: Threat Level (Modified by Aggressiveness and Caution)
        $rawThreat = 1 - min(1, ($worldData['enemy_dist'] ?? 1000) / 1000);
        $perceptions[] = $rawThreat * ($this->traits['aggressiveness'] / $this->traits['caution']);

        // Input 2: Resource Value (Modified by Expansionist trait)
        $rawResource = ($worldData['resource_val'] ?? 0) / 100;
        $perceptions[] = $rawResource * $this->traits['expansionist'];

        // Input 3: Territory Need
        $perceptions[] = $this->traits['expansionist'] * (1 - ($worldData['owned_land_ratio'] ?? 0));

        return $perceptions;
    }

    public function mutate(): void {
        parent::mutate(); // Mutate weights
        
        // Also mutate traits slightly for evolution
        foreach ($this->traits as $name => &$value) {
            if ((mt_rand() / mt_getrandmax()) < $this->mutationRate) {
                $value += (mt_rand(-10, 10) / 100);
                $value = max(0, min(1, $value));
            }
        }
    }
}


/*

3. Why this is Extensible

If you want to add a "Technological Development" trait later:
1. Add 'technophile' => 0.5 to the $traits array.
2. Add a line in perceive() that factors in $worldData['tech_nodes_nearby'] * $this->traits['technophile'].
3. The Neural Network automatically adapts because that trait is now just another input signal it has to account for.

Comparison of Archetypes

Archetype     Aggressiveness      Expansionist      Resulting Emergent Behavior
Berserker     0.9                 0.4               Attacks as soon as threat > 0.1C
olonialist    0.3                 0.9               Ignores enemies to build far-flung outposts
Turtle        0.1                 0.1               Stays near base, high caution weights

*/


