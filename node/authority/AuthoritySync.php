<?php
namespace Cartographica\Authority;

/*

The Authority Server Controller (AuthoritySync.php)

This part of the code lives on your main game servers. It monitors the "physical" world and notifies
the AI Module when something significant happens.

*/

class AuthoritySync {
    private $redis;

    public function __construct($host = '127.0.0.1') {
        $this->redis = new \Redis();
        $this->redis->connect($host);
    }

    /**
     * Triggered when a physical event occurs in the game world
     */
    public function emitEvent(string $type, array $payload, bool $isGlobal = true) {
        $packet = [
            'event' => $type,
            'payload' => $payload,
            'timestamp' => microtime(true)
        ];

        // Push to the Global AI Intelligence Stream
        $channel = $isGlobal ? 'global_events' : 'local_events_region_1';
        $this->redis->publish($channel, json_encode($packet));
    }
}

// Example: When a player builds a bridge to a new island
$sync = new AuthoritySync();
$sync->emitEvent('ISLAND_CONNECTED', [
    'from' => 'island_A',
    'to' => 'island_B',
    'discovered_by' => 'player_99'
]);
