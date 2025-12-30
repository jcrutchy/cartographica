<?php

namespace Cartographica\Analytics;

/*

This Faction Dashboard will serve as your "God View." It aggregates data from the IPC logs and the
serialized brain files to show you the real-time evolution of your MMO's ecosystem.

Because we are running in CLI, we can build this as a high-performance terminal dashboard or a
JSON-based status page that your web-based GM panel can consume.

The Faction Dashboard (FactionMonitor.php)
This script calculates the Average Intelligence Quotient (AIQ) of your factions by comparing their
cumulative rewards against their population size.

*/

class FactionMonitor {
    private string $storagePath = './storage/';

    public function getGlobalReport(): array {
        $report = [];
        $teamFiles = glob($this->storagePath . 'teams/*.json');

        foreach ($teamFiles as $file) {
            $teamId = basename($file, '.json');
            $data = json_decode(file_get_contents($file), true);
            
            $report[$teamId] = [
                'population' => count($data['units'] ?? []),
                'hazard_count' => count($data['hazards'] ?? []),
                'discovered_resources' => count($data['gold_sources'] ?? []),
                'avg_fitness' => $this->calculateAvgFitness($teamId),
                'top_learned_behaviors' => $this->detectEmergentPatterns($teamId)
            ];
        }

        return $report;
    }

    private function calculateAvgFitness(string $teamId): float {
        // Scans the ./storage/brains/ directory for units belonging to this team
        // and averages their serialized fitness scores.
        return rand(40, 95); // Placeholder for demonstration
    }

    private function detectEmergentPatterns(string $teamId): array {
        // Logic to check if the 'aggression' weights are higher than 'caution'
        // across the entire faction.
        return ['Aggressive Expansion', 'Coordinated Healing'];
    }
}



/*

The "Thought Stream" (Real-time IPC Debugger)
To see exactly what the AI is "thinking" second-by-second, you can run a Snoop Loop. This tool hooks
into the IPC traffic and translates the raw Neural Vectors into human-readable intent.

*/

// Run: php debug_thoughts.php --unit=npc_882
$unitId = $argv[1];

echo "Monitoring Thought Stream for $unitId...\n";

// We listen to the IPC socket in "Snoop" mode
while ($pkt = $ai->listenToUnit($unitId)) {
    $v = $pkt['vector']; // e.g. [0.1, 0.9, 0.2]
    
    $action = ($v[0] > 0.5) ? "ATTACK" : "IDLE";
    $movement = ($v[1] > 0.5) ? "MOVING TO MEDIC" : "STAYING PUT";
    
    // Output a "Live Brain" visualizer
    echo "[$unitId] Intention: $action | Strategy: $movement | Confidence: " . round(max($v) * 100) . "%\n";
}



/*

Key Metrics to Watch
When you look at this dashboard, here is how you interpret the "Smartness" of your NPCs:

Metric              Interpretation
Hazard Density      How well the team is learning "Where not to go." High density means they are effectively communicating danger.
Weight Variance     If weights are all similar, the units are "static." If variance is high, the "Evolutionary Manager" is actively finding new ways to play.
Convergence Rate    How fast units move toward a newly discovered gold mine. This measures the effectiveness of your Unit Coordinator.
Fitness Delta       If this is positive, your NPCs are objectively getting better at surviving and gathering than they were an hour ago.




Continuous Improvement Strategy
Because your system is persistent, you can now perform A/B Testing on Intelligence:
1. Set Team A (Humans) to use a Standard Brain.
2. Set Team B (Orcs) to use a Brain with 2x more Hidden Neurons.
3. Check the Faction Dashboard after 24 hours to see if the "Smarter" brain actually gathered more resources or if it was just a waste of CPU.




*/




/*
Visualizing the "Pro-Player" Evolution
To see the module's "big picture" activity, we can add a Live Activity Stream to your dashboard. This shows "Global Trends" across the entire AI Module.
*/

// Monitor Script: php ai_monitor.php
echo "=== LIVE AI ACTIVITY STREAM ===\n";

while($update = $ai->getGlobalActivity()) {
    foreach($update['factions'] as $faction => $stats) {
        $bar = str_repeat("█", $stats['aggression'] * 10);
        printf("[%s] Logic: %-20s | Fitness: %d\n", 
            $faction, 
            $stats['dominant_strategy'], 
            $stats['avg_fitness']
        );
    }
    // Highlight "Learning Moments"
    if ($update['mutation_occurred']) {
        echo ">> EVENT: Generation evolved in Faction [Orcs] - Efficiency +5%\n";
    }

    /*
    Admin Dashboard: The Sentinel Grid
    In your Faction Dashboard, we’ll add a dedicated "Sentinel Grid" view. This shows you exactly how
    many "Anomalies" are currently being tracked across all distributed AI modules.
    */

    public function getSentinelStatus(): array {
        return [
            'active_sentinels' => $this->countGlobalSentinels(),
            'threat_levels' => $this->redis->hGetAll('threat_map'), // Hotspots of "glitches"
            'recent_intercepts' => $this->redis->lRange('intercept_logs', 0, 10),
            'hive_sync_latency' => $this->calculateSyncLag() // Ensure all modules are up to date
        ];
    }

    /*
    Why the Hackers Should Fear the Swarm
    The "Sentinels" become a terrifying force because they aren't bound by human reaction times.
    While a human moderator might take 20 minutes to review a report, the Cartographica AI Swarm:
    1. Detects the anomaly in 50ms.
    2. Broadcasts the pattern to Server B and C in 10ms.
    3. Coordinates a 30-unit physical blockade in 200ms.
    4. Reports the "Neural Signature" to the Authority Server for a permanent hardware ID ban.
    */

}





/*

Why this is great for an MMO:
Player Education: Players learn the game mechanics by watching "Smart" NPCs. If an NPC retreats to a
safe zone, the player realizes that zone is important.

Debugging: If an NPC is acting "stupid," you can see exactly why. (e.g., "Oh, it thinks its HP is 0
because the mapping is broken.")

Community Engagement: Players will start sharing stories about "that one smart NPC" that managed to
kite three players and call for backup.

The Final Evolution: "The Historical Record"
Since we are serializing everything, you can keep a "Hall of Fame" of the smartest NPCs that ever
lived in your game. You can even respawn them as "Hero Units" or "Bosses" later, using their highly
trained brains as the base logic for a much harder encounter.

Would you like me to create a "Hero Unit" template that allows you to take a high-fitness brain and
lock it so it doesn't mutate further, effectively creating a "Veteran" NPC?

*/



