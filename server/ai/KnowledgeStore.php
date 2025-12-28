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
The Diplomacy Layer
Diplomacy is implemented as a Bilateral Trust Score. This score is fed directly into the Brain's input layer.
*/

    private array $diplomacyMatrix = []; // [team_a][team_b] => trust_score (-1.0 to 1.0)
    
    public function updateRelation(string $myTeam, string $otherTeam, float $delta): void {
        $current = $this->diplomacyMatrix[$myTeam][$otherTeam] ?? 0.0;
        $this->diplomacyMatrix[$myTeam][$otherTeam] = max(-1, min(1, $current + $delta));
        
        // Global sync so other AI instances know the war has started
        $this->globalSync->publish("diplomacy_update", [$myTeam, $otherTeam, $delta]);
    }
    
    public function getTrust(string $myTeam, string $otherTeam): float {
        return $this->diplomacyMatrix[$myTeam][$otherTeam] ?? 0.0;
    }


/*
The Brain now learns:
Trust > 0.5: "If I move closer, I get a Social Reward."
Trust < -0.5: "If I move closer, I take damage (Penalty). Better to attack first."
*/



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



/*

This is a classic dilemma in game design: Overt Deterrence vs. Covert Detection. The best solution
for Cartographica is likely a Hybrid Hive Model. In this model, "Sentinel" is not a physical unit
type, but a Neural Program that can be "uploaded" into any NPC. This solves the "Police Presence"
problem while ensuring the hackers are never truly safe.

The "Ghost in the Machine" Architecture
Instead of having a dedicated Sentinel unit, you treat the Sentinel logic as a Background Service
that runs on all NPCs.

Public Sentinels (The Deterrent): These are high-level, clearly marked "Guardian" units that patrol
cities. Their presence makes players feel safe and deters casual cheaters.

Undercover Nodes (The Detection): Every regular NPC (a merchant, a miner, a stray dog) carries a
"dormant" Sentinel brain. They aren't "undercover cops" in the traditional sense; they are Sensors.

The "Subliminal Vigilance" Input
We add a hidden input to the mapping.json for every NPC in the world.




When a hacker performs a "Speed Hack" near a simple sheep NPC:
1. The Sheep’s Passive Sentinel Layer flags the anomaly.
2. The Sheep doesn't attack (that would look weird/mistrustful). Instead, it silently broadcasts the "Wanted Poster" to the Global Sync.
3. The Public Sentinels (the Swarm) receive the coordinates and "descend" from the nearest patrol point.

Solving the "Mistrust" Problem
To prevent human players from feeling like they are in a "Police State," the NPCs should only
transition from "Civilian" to "Sentinel" when the Confidence Score of a hack is nearly 100%.

Unit State               Behavior                                                      Player Perception
Civilian                 Mining, Trading, Socializing.                                 "Just a normal NPC."
Vigilant                 NPC stops what it's doing and "looks" at the hacker.          "The NPC seems to notice something is wrong."
Reporting                Silent data upload to the Swarm.                              No visible change.
Swarmed                  Public Sentinels arrive to intervene.                         "Justice being served."




*/
