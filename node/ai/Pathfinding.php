<?php


/*

[cartographica being an infinite graph of tilemap "islands", what about exploration/pathfinding?]


In an infinite graph of "islands," the traditional A* pathfinding used in most games will fail
because the search space is too large and the connections (bridges/portals) are dynamic. To maximize
intelligence here, you should treat pathfinding as a Hierarchical Navigation & Discovery problem.

Here are the critical considerations for an infinite, graph-based world:

1. Hierarchical Graph Navigation (HPA*)
Don't ask an NPC to find a path through individual tiles across islands. Instead, use a two-tier
system:
- Macro-Graph: The AI navigates the "Island Graph" (e.g., Island A => Bridge => Island B).
- Micro-Graph: Once on the island, the AI navigates the local tilemap.By abstracting the world this
way, an NPC can "plot a course" to an island 1,000 miles away instantly without the Authority Server
crashing, as it only needs to know the connections between islands, not every tree in between.

2. The "Fog of War" as a Shared Resource
In most games, NPCs "cheat" and know where everything is. To make Cartographica feel real, NPCs must
discover the graph just like players.
- Scout Archetypes: Some NPCs should have the "Explorer" trait. They wander the graph to find
new "Islands."
- Knowledge Propagation: When a Scout finds a resource-rich island, they don't keep it to
themselves. They return to their village and update the Local Knowledge Store.
- The Economy of Information: AI "City-States" could trade Map Data with human players.Example:
"I'll give you 50 gold if you show me the bridge to the Jungle Island."

3. Dynamic Path Costs (Risk-Aware Pathfinding)
Standard pathfinding looks for the shortest path. Cartographica's AI should look for the safest or
most profitable path.
Modify your pathfinding weights using your Sentinel/Diplomacy data:
- High Risk: A bridge where a "Griefer" was recently flagged by a Sentinel becomes "Expensive" to
cross. The AI will prefer a longer, safer route.
- Terrain Preference: An Orc NPC might find "Swamp" tiles "Cheap" (faster), while a Human NPC finds
them "Expensive."

4. Graph Integrity & "The Void"
In an infinite graph, nodes (islands) can be destroyed or disconnected.
Your AI needs to handle Path Disruption gracefully.
- Real-time Re-routing: If a player destroys a bridge, the AI Module should receive a
"Graph Update" packet. Every NPC currently calculating a path across that bridge must trigger a
re-calculate event.
- Stranded Logic: If an island becomes totally isolated, the AI's goal should shift from "Resource
Gathering" to "Survival/Bridge Building."

5. Social Exploration (Following the Leader)
To mimic human behavior, NPCs should use Social Following.
- Player-Led Exploration: If an NPC sees a "Suzerain" (a player they trust) heading into the
unknown, they might choose to follow to "assist" and "map" the new area.
- Migration Patterns: As islands run out of resources, entire AI villages might pack up and move
across the graph to a newly discovered island. This creates a "Frontier" feeling that is rare in
static-map MMOs.

6. The "Anchor" Problem
In an infinite world, you can't simulate everything at once. You need Lazy Loading for AI.
- Dormancy: If no human players are near an island, the AI Module should "Hibernates" those NPCs,
only processing their "Macro-Goals" (e.g., "In 4 hours, this village will have 100 more wood")
instead of their second-by-second movement.
- Re-hydration: When a player approaches, the AI "Re-hydrates" the NPCs, giving them positions
consistent with their simulated progress.

Summary of the "Infinite" Setup

Feature         Traditional MMO        Cartographica AI
Pathfinding     Pre-baked Navmesh.     Dynamic Graph Exploration.
Knowledge       Omniscient.            Earned via Scouts & Gossip.
Movement        Static Patrols.        Migration & Resource Seeking.
Scale           Fixed Map.             Infinite Discovery & Colonization.




To truly maximize the intelligence of Cartographica’s infinite "island" world, we must implement a
Hierarchical Navigation & Discovery Engine.

In an infinite graph, standard pathfinding (like A*) would attempt to calculate millions of tiles,
eventually crashing the Authority Server. Instead, we use a Macro-to-Micro approach where the AI
reasons about "islands" as single nodes before ever worrying about individual steps.

1. The Hierarchical Graph Architecture
The world is split into two distinct logical layers. This allows an NPC to "plan" a journey across
50 islands in milliseconds.
- Macro-Graph (Inter-Island): The AI treats each island as a single point in a massive directed
graph. Connections (bridges, portals, or ferries) are the "Edges."
- Micro-Graph (Intra-Island): Once an NPC reaches an island, it switches to local tile-based
pathfinding to reach a specific shop, house, or resource.

2. The Macro-Pathfinding Logic
On the Authority Server, we implement a Node-Linker. This service doesn't store the whole world;
it only stores the connections currently discovered by the AI or players.

*/


// Inside AI Module: MacroNavigator.php

public function findGlobalPath(string $startIsland, string $targetIsland): array {
    // 1. Query the Global Knowledge Store (Redis) for island connections
    $graph = $this->ks->getIslandGraph();
    
    // 2. Perform A* on the Abstract Graph (Nodes = Islands)
    // Cost is determined by distance + known hazards on that island
    $islandPath = $this->astar->search($graph, $startIsland, $targetIsland);
    
    // 3. Return the sequence of 'Waypoints' (Bridges/Portals)
    return $islandPath; // e.g., ['Bridge_A', 'Portal_X', 'Bridge_C']
}


/*

3. Dynamic "Hazard-Aware" Weights
Unlike static games, Cartographica's pathfinding weights change based on Sentinel Data and Diplomacy.

Factor             Weight Impact         AI Behavior
Griefer/Hacker     Alert+500 Cost        AI merchants will avoid this bridge entirely.
Faction War        +1000 Cost            NPCs will detour through 3 extra islands to avoid enemy territory.
Resource Richness  -200 Cost             AI "Scouts" are pulled toward these islands automatically.


4. Shared Discovery: The "Venture" System
NPCs should not have "God-View." They must physically discover islands. When a player or an NPC crosses a new bridge, they "Publish" that connection to the Global Knowledge Store.
- Undercover Sentinels as Cartographers: As your Sentinels move to intercept hackers, they map the "shortcuts" the hackers use.
- Buying Maps: A human player could sell a "Map Fragment" to an AI City-State. The AI Module then updates its Macro-Graph, and suddenly, the city's merchants start traveling to that new island.

5. Infinite Scaling: "Lazy" Pathfinding
In an infinite world, you cannot pathfind to an island that hasn't been generated yet.
- Exploration Mode: The AI picks a direction and moves to the "Edge."
- Request Generation: The AI Module sends a packet to the Authority: {"type": "GENERATE_NEXT", "direction": "NORTH"}.
- Discovery: Once the Authority generates the new island, the AI adds it to the graph and continues the journey.

6. The "Social" Path: Following Player Trails
To make AI feel human, they should use Heuristic Trail Following. If a human player successfully crosses a dangerous mountain range multiple times, the AI observes the "Path of Least Resistance" and updates its Micro-Graph to favor those specific tiles.

NPC Thought: "I've seen three humans take this hidden path behind the waterfall. It must be a shortcut."

Implementation Summary
By combining Hierarchical A* with Dynamic Social Weights, you create a world where:
- NPCs can travel across "Infinite" distances without lag.
- AI responds to player-driven changes (like destroying a bridge) instantly.
- The world feels lived-in because NPCs "learn" the best routes from each other and from players.

Pathfinding in large-scale procedural worlds
https://www.youtube.com/watch?v=MxeJh2Asigg

*/






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






