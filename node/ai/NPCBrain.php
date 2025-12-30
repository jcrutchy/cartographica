<?php

/*

(used by NPC_Agent_CLI.php)

Building a Neural Network in PHP is unconventional because PHP is typically synchronous and not
optimized for matrix mathematics (unlike Python). However, for a game mechanic, we can build a
lightweight Feed-Forward Neural Network (Perceptron).

To ensure "emergent behavior" and high performance:
1. Inference Only: We will run the "thinking" part (inference) in real-time, but avoid complex
"training" (backpropagation) during the HTTP request to keep it fast.
2. Neuroevolution: The best way to get emergent behavior in games is to start with random weights and
"mutate" the best performing NPCs over time, rather than training against a dataset.

Here is a highly optimized, dependency-free PHP class for an NPC Brain.

*/

class NPCBrain {
    private array $weightsInputHidden;
    private array $weightsHiddenOutput;
    private array $biasesHidden;
    private array $biasesOutput;
    
    private int $inputNodes;
    private int $hiddenNodes;
    private int $outputNodes;

    public function __construct(int $inputs, int $hidden, int $outputs) {
        $this->inputNodes = $inputs;
        $this->hiddenNodes = $hidden;
        $this->outputNodes = $outputs;

        // Initialize with random weights (-1 to 1) for chaos/emergence
        $this->randomize();
    }

    /**
     * The Thinking Process (Forward Pass)
     * Optimized with linear algebra approximations for speed.
     */
    public function think(array $inputs): array {
        if (count($inputs) !== $this->inputNodes) {
            throw new Exception("Input count mismatch");
        }

        // 1. Calculate Hidden Layer
        $hiddenOutputs = [];
        for ($h = 0; $h < $this->hiddenNodes; $h++) {
            $sum = $this->biasesHidden[$h];
            for ($i = 0; $i < $this->inputNodes; $i++) {
                $sum += $inputs[$i] * $this->weightsInputHidden[$i][$h];
            }
            // Sigmoid Activation: 1 / (1 + exp(-x))
            $hiddenOutputs[$h] = 1 / (1 + exp(-$sum)); 
        }

        // 2. Calculate Output Layer
        $finalOutputs = [];
        for ($o = 0; $o < $this->outputNodes; $o++) {
            $sum = $this->biasesOutput[$o];
            for ($h = 0; $h < $this->hiddenNodes; $h++) {
                $sum += $hiddenOutputs[$h] * $this->weightsHiddenOutput[$h][$o];
            }
            // Sigmoid Activation
            $finalOutputs[$o] = 1 / (1 + exp(-$sum));
        }

        return $finalOutputs;
    }

    /**
     * Randomize brain structure (Mutation)
     */
    public function randomize(): void {
        $this->weightsInputHidden = $this->generateMatrix($this->inputNodes, $this->hiddenNodes);
        $this->weightsHiddenOutput = $this->generateMatrix($this->hiddenNodes, $this->outputNodes);
        $this->biasesHidden = $this->generateArray($this->hiddenNodes);
        $this->biasesOutput = $this->generateArray($this->outputNodes);
    }

    // --- Helpers ---

    private function generateMatrix($rows, $cols): array {
        $matrix = [];
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $matrix[$i][$j] = (rand(-100, 100) / 100); // Float between -1.0 and 1.0
            }
        }
        return $matrix;
    }

    private function generateArray($size): array {
        $arr = [];
        for ($i = 0; $i < $size; $i++) {
            $arr[$i] = (rand(-100, 100) / 100);
        }
        return $arr;
    }

    /**
     * Save brain state to JSON (for database storage)
     */
    public function serialize(): string {
        return json_encode([
            'w_ih' => $this->weightsInputHidden,
            'w_ho' => $this->weightsHiddenOutput,
            'b_h'  => $this->biasesHidden,
            'b_o'  => $this->biasesOutput
        ]);
    }

    /**
     * Load brain from JSON
     */
    public function load(string $json): void {
        $data = json_decode($json, true);
        $this->weightsInputHidden = $data['w_ih'];
        $this->weightsHiddenOutput = $data['w_ho'];
        $this->biasesHidden = $data['b_h'];
        $this->biasesOutput = $data['b_o'];
    }
}




/*

Implementation Example (The Emergent Behavior)
In this scenario, we have a "Colonist" in your Cartographica game. We feed it data about its environment, and the neural net decides what to do.

Inputs (Normalized 0.0 - 1.0):
1. Health (0 = dead, 1 = full)
2. Enemy Proximity (0 = far, 1 = close)
3. Resource Proximity (0 = far, 1 = close)

Outputs:
1. Attack
2. Flee
3. Gather

*/


// 1. Initialize a Brain: 3 Inputs, 4 Hidden Neurons, 3 Outputs
$colonistBrain = new NPCBrain(3, 4, 3);

// --- Simulation Loop ---

// Current State of the Colonist (Inputs)
$health = 0.2;        // Low Health
$enemyNear = 0.9;     // Enemy is very close
$resourceNear = 0.1;  // Resources are far away

$inputs = [$health, $enemyNear, $resourceNear];

// 2. The Brain Decisions
$decisions = $colonistBrain->think($inputs);

// Map output to actions
$actions = ['Attack', 'Flee', 'Gather'];

// Find the strongest impulse
$maxVal = 0;
$chosenActionIndex = 0;

echo "--- Neural Debug ---\n";
foreach ($decisions as $index => $strength) {
    echo "Urge to " . $actions[$index] . ": " . number_format($strength * 100, 2) . "%\n";
    if ($strength > $maxVal) {
        $maxVal = $strength;
        $chosenActionIndex = $index;
    }
}

echo "\nFINAL DECISION: " . $actions[$chosenActionIndex] . "\n";


/*

How to achieve "Emergence"
Since we aren't training this with backpropagation (which is slow), you get emergent behavior by Evolution:

Spawn: Create 10 NPCs with random brains (randomize()).

Evaluate: Let them run for a day in-game.

Selection: Did NPC #4 survive the longest or gather the most gold? Save its JSON string (serialize()).

Mutate: When spawning a new NPC, load NPC #4's JSON, but slightly tweak a few weights by a random percentage.

Result: Over time, your NPCs will "learn" to flee when health is low without you explicitly programming if (health < 20) flee.

Performance Optimization Tips for PHP
JIT: Ensure you are running PHP 8.0+ with JIT enabled in php.ini. This makes the looping math significantly faster.

Arrays, not Objects: Notice I used primitive arrays ([]) inside the class logic rather than creating Neuron objects. Object instantiation in PHP is expensive; array access is fast.

Caching: Do not instantiate the brain every single frame/request if possible. If this is a turn-based web game, load the JSON string from Memcached or Redis, pass it to the load() function, run think(), and destroy the object.

Would you like me to create a "Mutation" function so you can breed these NPCs?






To maximize the "intelligence" of the Cartographica system, you should focus on Social Cognitive
Architectures rather than just tactical ones. The goal is to move from "bots that react" to "agents
that reason socially."

In 2025, the frontier of AI in gaming has shifted toward Predictive Social Modeling. This means your
NPCs don't just calculate pathfinding; they calculate intentionality.

The "Theory of Mind" Module
Most game AI only knows what is (positions, HP). To make Cartographica unlike any other game,
give NPCs a Theory of Mind layer. This is a sub-network that maintains a "Mental Model" of
every nearby player.
- NPC Belief: "I believe Player A is friendly because they traded with me."
- NPC Meta-Belief: "I believe Player A believes I am weak because I haven't upgraded my armor."
- Emergent Behavior: The NPC might "bait" the player by acting weaker than they are, testing if the
player's friendliness is genuine or just waiting for a moment to strike.

High-Fidelity Reciprocity (The Nash Equilibrium)
To solve the "worker theft" problem, use Recursive Reasoning (also called Level-k Reasoning).
When a player interacts with an NPC, the NPC runs a quick simulation: "If I were this player,
would stealing from me be a rational long-term move?"
- Human-Like Skepticism: If a player is "too nice," the NPC becomes suspicious of a "Gilded Trap."
- Economic War: If a player steals, the NPC doesn't just attack; it might start a "Trade Embargo"
across the distributed network. Suddenly, no NPC in the region will sell to that player, effectively
"socially banning" them without needing a moderator.


Distributed "Neural Gossip" (Global Synaptic Plasticity)
Since your system is distributed, use a Vector Database (like Pinecone or Milvus) as a
"Collective Subconscious" for all NPCs.

Phase          Local Action (Server A)              Global Impact (All Servers)
Observation    Player A uses a specific exploit.    The pattern is hashed into a vector.
Sync           Sentinel flags the pattern.          The vector is pushed to the global DB.
Evolution      Local NPCs adapt.                    All NPCs pull the new "Anti-Pattern" weights.

The "Living History" Persistence
To make the AI feel truly human, implement Lossy Memory. NPCs should forget trivial details but
remember emotional "Peaks."
- Emotional Anchoring: If a player saves an NPC from a dragon, that NPC's weights for that player are
"frozen" and protected from decay.
- Generational Knowledge: When an NPC unit "dies" and is replaced, it passes a "Legacy Packet" to its
successor. The new unit inherits the "Social Grudges" of the old one, preventing players from
"resetting" their bad reputation by just killing the witness.


Advanced Anti-Cheat: The "Humanity Score"
Instead of checking for code injection, the AI evaluates the "Physical Plausibility" of actions.
- Micro-Correction Analysis: Human players constantly make tiny, sub-pixel corrections in movement.
Bots are too efficient.
- The Turing Trap: A Sentinel might deliberately place a "distraction" (like a rare item spawn) in a
weird location. A human will likely investigate; a bot focused on a mission path will ignore it with
100% mathematical precision—flagging itself as non-human.








*/
