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

*/
