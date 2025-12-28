<?php

namespace Cartographica\AI;

/*
The Global Knowledge Store (Shared Memory)
This store acts as the "Group Mind." When a Scout finds a resource, it logs it here. When a Miner is
bored, it looks here.
*/

class KnowledgeStore {
    private array $data = [];
    private string $storageDir = './persistence/teams/';

    public function __construct(string $teamId) {
        $this->teamId = $teamId;
        $this->load();
    }

    public function logFact(string $category, string $id, array $details): void {
        $this->data[$category][$id] = array_merge($details, ['time' => time()]);
    }

    public function query(string $method, array $params, float $qx, float $qy): float {
        // Returns a normalized 0.0 - 1.0 signal based on distance/relevance
        if ($method === 'findNearest') {
            $type = $params[0];
            $nearest = $this->getNearest($type, $qx, $qy);
            if (!$nearest) return 0.0;
            
            // Normalize distance: 1.0 = right on top of it, 0.0 = far away
            $dist = sqrt(($nearest['x'] - $qx)**2 + ($nearest['y'] - $qy)**2);
            return 1.0 - min(1, $dist / 1000); 
        }
        return 0.0;
    }

    private function getNearest(string $type, float $x, float $y): ?array {
        $found = null; $min = INF;
        foreach ($this->data[$type] ?? [] as $item) {
            $d = ($item['x'] - $x)**2 + ($item['y'] - $y)**2;
            if ($d < $min) { $min = $d; $found = $item; }
        }
        return $found;
    }


/*
Team Learning (The "Ghost" Memory)
When a unit dies, it should leave behind a Hazard Zone marker on the Shared Blackboard. This marker
tells other units: "Regardless of what your brain thinks, there is death here."
*/
    
    public function logDeath(float $x, float $y, string $cause): void {
        $this->data['hazard_zones'][] = [
            'x' => $x,
            'y' => $y,
            'cause' => $cause,
            'severity' => 1.0,
            'expires' => time() + 300 // Hazard lasts 5 minutes
        ];
    }
    

/*
Death Analysis: "The Autopsy"
To prevent multiple units from dying to the same threat, the Coordinator processes death packets and
updates the Incompatibility Matrix.
*/
    public function logDeath(float $x, float $y, array $context): void {
        $hazardId = bin2hex(random_bytes(4));
        
        $this->data['hazards'][$hazardId] = [
            'pos' => ['x' => $x, 'y' => $y],
            'killer_type' => $context['killed_by'], // e.g., 'heavy_turret'
            'victim_type' => $context['unit_type'], // e.g., 'light_scout'
            'severity' => 1.0,
            'timestamp' => time()
        ];
    
        // Log the "Hard Lesson"
        // If a light unit died to a turret, all light units get a 'danger' signal
        $this->data['incompatibility'][$context['unit_type']][] = $context['killed_by'];
    }

    
    public function getHazardMultiplier(float $qx, float $qy): float {
        $penalty = 0.0;
        foreach ($this->data['hazard_zones'] as $zone) {
            $dist = sqrt(($zone['x'] - $qx)**2 + ($zone['y'] - $qy)**2);
            if ($dist < 200) { // Influence radius
                $penalty += (1.0 - ($dist / 200));
            }
        }
        return min(1.0, $penalty);
    }


    public function save(): void {
        file_put_contents($this->storageDir . "{$this->teamId}.json", json_encode($this->data));
    }

    private function load(): void {
        $path = $this->storageDir . "{$this->teamId}.json";
        if (file_exists($path)) $this->data = json_decode(file_get_contents($path), true);
    }
}



/*

The Integrated Result:
Mining Swarm: A scout finds gold. The Blackboard sets global_urgency to high. All nearby Miners
(regardless of their current brain state) see a high global_priority_pull. Their neural nets have
learned that following this "pull" signal leads to massive payload_increased rewards. They converge.

Avoidance Learning: A Warrior enters a canyon and dies to a turret. It sends a logDeath packet via
IPC. Now, every other unit—even those who haven't seen the turret—receives a high team_danger_sense
input when they approach that canyon. Their brain interprets this as a "virtual wall" and they path
around it.

Cross-Unit Medic Call: A wounded unit's brain fires the "Help" neuron. This writes a "Need" to the
Blackboard. A Medic's brain sees the "Need" as a global_priority_pull and moves to the location.

*/



