<?php

/*

The AI Module Event Listener (CoreEvents.php)
This part lives in the AI Module. It "listens" to the global stream and updates the neural weights or
memory of the NPCs it controls.

*/


// Inside your AI Core loop

public function startEventListener() {
    $this->redis->subscribe(['global_events'], function($redis, $chan, $msg) {
        $event = json_decode($msg, true);
        $this->handleGlobalEvent($event);
    });
}

private function handleGlobalEvent(array $event) {
    switch ($event['event']) {
        case 'HACKER_ALERT':
            // All Sentinels globally increase vigilance
            $this->sentinelManager->alertSwarm($event['payload']['signature']);
            break;

        case 'TECH_DISCOVERY':
            // Update the global Archetype DNA
            $this->archetypeManager->evolve($event['payload']['tech_id']);
            break;

        case 'HELP_REQUEST':
            // Find nearby NPCs to assist
            $this->unitCoordinator->dispatchReinforcements($event['payload']['coords']);
            break;
    }
}






/*

Hierarchical Pathfinding (The Infinite Graph)
To handle pathfinding across an infinite graph of islands, we don't calculate every tile.
We calculate Nodes (Islands) and Edges (Bridges).

*/


/**
 * Macro-Pathfinding for the Infinite Graph
 */
public function getMacroPath(string $startNode, string $targetNode) {
    // 1. Get the current 'World Graph' from the Authority's Knowledge Store
    $graph = $this->authority->getKnownIslands();
    
    // 2. Perform A* on Island IDs only (not tiles)
    $islandSequence = $this->astar->search($graph, $startNode, $targetNode);
    
    // 3. Return a list of 'Portal' or 'Bridge' coordinates for the NPC
    return $islandSequence; 
}
