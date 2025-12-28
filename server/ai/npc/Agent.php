<?php

namespace Cartographica\AI;

/*

To keep this clean, modular, and performant, we will use a Component-Based Architecture.

We’ll separate the Traits (the "DNA"), the Perception (the "Senses"), and the Brain (the "Logic").
This allows you to define a new NPC unit type simply by swapping a trait array, while the core logic
remains untouched.

The Architecture: "The Archetype Factory"
The Core Learning Agent (NPC/Agent.php)
This class handles the neural math and the mutation logic.

*/

class Agent {
    public array $weights;
    public array $traits = [];
    public array $memory; // Internal state buffer
    private int $inputCount;
    private int $outputCount;

    public function __construct(int $inputs, int $outputs, array $initialTraits = []) {
        $this->inputCount = $inputs;
        $this->outputCount = $outputs;
        $this->traits = $initialTraits;
        $this->memory = array_fill(0, $outputs, 0.0); // Init empty memory
        $this->randomizeWeights();
    }

    private function randomizeWeights(): void {
        // Flat array for performance, represents a simple matrix
        for ($i = 0; $i < ($this->inputCount * $this->outputCount); $i++) {
            $this->weights[$i] = (mt_rand(-100, 100) / 100);
        }
    }

    public function think(array $inputs): array {
        $outputs = array_fill(0, $this->outputCount, 0);
        for ($o = 0; $o < $this->outputCount; $o++) {
            for ($i = 0; $i < $this->inputCount; $i++) {
                $outputs[$o] += $inputs[$i] * $this->weights[($o * $this->inputCount) + $i];
            }
            // Fast Sigmoid Approximation
            $outputs[$o] = 1 / (1 + exp(-$outputs[$o]));
        }
        return $outputs;
    }

    public function think(array $inputs): array {
        // Incorporate memory into the input stream
        // We append the memory buffer to the external world inputs
        $combinedInputs = array_merge($inputs, $this->memory);
        
        $finalOutputs = array_fill(0, $this->outputCount, 0);
        
        // Matrix multiplication (Optimized for PHP CLI)
        for ($o = 0; $o < $this->outputCount; $o++) {
            $sum = 0;
            foreach ($combinedInputs as $i => $val) {
                $sum += $val * $this->weights[($o * count($combinedInputs)) + $i];
            }
            $finalOutputs[$o] = 1 / (1 + exp(-$sum));
        }

        // Store current outputs in memory for the NEXT tick
        // Weighted by the 'memory_retention' trait
        $retention = $this->traits['memory_retention'] ?? 0.5;
        foreach ($finalOutputs as $idx => $val) {
            $this->memory[$idx] = ($this->memory[$idx] * $retention) + ($val * (1 - $retention));
        }

        return $finalOutputs;
    }

    /*
    Implementation Checklist
    Storage: Create a ./brains/ directory. Use $id.json to store individual unit "experience."
    
    IPC Protocol: Update your IPC to handle BROADCAST messages. When a unit says "Medic!", the server
    should ensure other units "perceive" that call in their next tick data.
    
    JSON Config: In archetypes.json, add a communication_range trait. Use this to determine if a unit
    is close enough to hear a "Medic!" cry.

    How this creates "Cool" Gameplay:
    Because the Neural Net is receiving a "Medic Available" signal, it will learn over time that
    moving toward that signal when HP is low results in a Reward (survival). You don't have to
    hard-code the pathfinding; the Brain will start "leaning" its movement vector toward the Medic's
    coordinates because that's where the reinforcement learning points are.

    */

    public function processTick(array $worldData, Blackboard $blackboard) {
        // 1. Update the shared knowledge first
        $blackboard->updateUnit($worldData['id'], [
            'type' => $worldData['type'],
            'hp' => $worldData['hp'],
            'x' => $worldData['x'],
            'y' => $worldData['y']
        ]);
    
        // 2. Fact-Based Logic (Heuristics)
        $socialCues = [0, 0]; // [MedicProximity, SafePointProximity]
        
        if ($worldData['hp'] < 30) {
            $medic = $blackboard->findNearest('medic', $worldData['x'], $worldData['y']);
            if ($medic) {
                // Feed the location of the nearest medic into the Neural Net as an input
                $socialCues[0] = 1.0; // Signal: "Medic available"
                
                // Send a specific IPC command if critical
                $this->broadcastMessage("MEDIC!", $worldData['id']);
            }
        }
    
        // 3. Neural Decision
        // Inputs: [HP, Threat, Resource, MedicAvailable, SafePointAvailable, Memory...]
        $inputs = array_merge(
            [$worldData['hp']/100, $worldData['threat']], 
            $socialCues
        );
        
        $decisionVector = $this->agent->think($inputs);
        
        // 4. Send to IPC
        $this->sendAction($decisionVector, $medic['location'] ?? null);
    }


    public function mutate(float $rate = 0.05): void {
        foreach ($this->weights as &$w) {
            if ((mt_rand() / mt_getrandmax()) < $rate) {
                $w += (mt_rand(-50, 50) / 100);
            }
        }
        // Traits can also drift over generations
        foreach ($this->traits as &$t) {
            if ((mt_rand() / mt_getrandmax()) < $rate) {
                $t = max(0, min(1, $t + (mt_rand(-10, 10) / 100)));
            }
        }
    }

    /*
    Serialization & Persistence
    We add save() and load() to the Agent class so that brains aren't lost on restart.
    */

    public function serialize(): string {
        return json_encode([
            'weights' => $this->weights,
            'traits'  => $this->traits,
            'memory'  => $this->memory
        ]);
    }
    
    public static function fromFile(string $path): self {
        $data = json_decode(file_get_contents($path), true);
        $agent = new self(count($data['weights']), 0, $data['traits']); // Logic to match counts
        $agent->weights = $data['weights'];
        $agent->memory = $data['memory'];
        return $agent;
    }

}






/*
The Memory-Enabled Agent
We add a $memory array. In every tick, the brain's own previous output is fed back into the next
input cycle. This allows the NPC to develop "intent" (e.g., if I moved North last tick, I am likely
to continue moving North).
*/

