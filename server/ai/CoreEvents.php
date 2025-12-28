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

    while (true) {
        // 1. Process standard game ticks (Movement/Combat)
        $this->processIpcTicks();
    
        // 2. Process Global Events (The "News")
        if ($event = $this->eventBus->getNext()) {
            switch($event['type']) {
    
    
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
        
                case 'NEW_ISLAND':
                    $this->updateMacroGraph($event['data']);
                    break;
        
                case 'TECH_UPGRADE':
                    $this->evolveArchetypes($event['data']);
                    break;
        
            }
        }
    }
}






/*


Why this makes Cartographica "Uniquely Intelligent":
Instead of NPCs just following individual scripts, they are part of a Global Information Network.
An event on Island A can cause an NPC on Island Z to change its long-term goals. This creates a
"Grand Strategy" layer that emerges naturally from the ground up.

Would you like me to create the "Messenger Archetype"? This is a specialized NPC that physically
carries these "Event Packets" between islands for a more immersive, "non-instant" information flow.




