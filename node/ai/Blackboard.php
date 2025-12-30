<?php

namespace Cartographica\AI;

/*

[units to remember everything they know about the world (maybe as a centralized data store for a
given ai player that all its units can access and build on) so that decisions can be assisted. for
example, if a warrior unit is wounded it should know if there are any medic units nearby and to
communicate its injury (like calling "medic!") and if possible moving toward a suitable shared safe
meeting point)]

This requires moving from a "Simple Brain" to a Knowledge Graph architecture. To achieve this, we
will separate Instinct (the Neural Net) from Knowledge (the Shared Data Store).

In this setup, each AI Player (Team) has a Blackboard. Units write "Facts" to the Blackboard (e.g.,
"I am a Medic at X,Y") and read "Needs" from it (e.g., "A Warrior needs help at Z,W").

The Shared Blackboard (Team Intelligence)
This is a centralized object shared by all units belonging to the same team_id.

*/

class Blackboard {
    private array $knowledge = [
        'units' => [],        // Locations and types of all allies
        'threats' => [],      // Known enemy positions
        'meeting_points' => [] // Shared safe zones
    ];

    public function updateUnit(string $id, array $data): void {
        $this->knowledge['units'][$id] = array_merge(
            $this->knowledge['units'][$id] ?? [],
            $data,
            ['timestamp' => microtime(true)]
        );
    }

    public function findNearest(string $type, float $currentX, float $currentY): ?array {
        $bestDist = INF;
        $found = null;

        foreach ($this->knowledge['units'] as $id => $info) {
            if (($info['type'] ?? '') === $type) {
                $dist = sqrt(($info['x'] - $currentX)**2 + ($info['y'] - $currentY)**2);
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $found = $info;
                }
            }
        }
        return $found;
    }

    public function getKnowledge(): array { return $this->knowledge; }
}
