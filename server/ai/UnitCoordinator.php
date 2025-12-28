<?php

namespace Cartographica\AI;

/*

This Unit Coordinator acts as the "CEO" of the team. While the Neural Nets handle the individual
instinct of the units, the Coordinator manages the allocation of labor. This prevents the "clumping"
problem where every single unit abandons its post to chase one piece of gold.

The Coordinator Class (Resource Management)
The Coordinator tracks "Job Openings" and assigns units based on proximity and archetype suitability.

*/

class UnitCoordinator {
    private array $assignments = []; // [unit_id => job_id]
    private array $jobRegistry = [];  // [job_id => ['required' => 3, 'current' => 0]]

    public function registerDiscovery(string $jobId, string $type, int $capacity): void {
        if (!isset($this->jobRegistry[$jobId])) {
            $this->jobRegistry[$jobId] = [
                'type' => $type,
                'limit' => $capacity,
                'assigned' => []
            ];
        }
    }

    /**
     * Determines if a unit should switch to a new high-priority task
     */
    public function shouldRecruit(string $unitId, string $unitType, float $x, float $y): ?string {
        foreach ($this->jobRegistry as $jobId => &$job) {
            // 1. Is the unit type compatible? (e.g., Miners for Gold)
            if ($job['type'] !== $unitType) continue;

            // 2. Is there room?
            if (count($job['assigned']) < $job['limit']) {
                // 3. Efficiency check: Only recruit if not already on a better task
                if ($this->isAssigned($unitId)) return null;

                $job['assigned'][] = $unitId;
                $this->assignments[$unitId] = $jobId;
                return $jobId;
            }
        }
        return null;
    }

    public function releaseUnit(string $unitId): void {
        $jobId = $this->assignments[$unitId] ?? null;
        if ($jobId) {
            $key = array_search($unitId, $this->jobRegistry[$jobId]['assigned']);
            unset($this->jobRegistry[$jobId]['assigned'][$key]);
            unset($this->assignments[$unitId]);
        }
    }

    private function isAssigned(string $id): bool { return isset($this->assignments[$id]); }
}
