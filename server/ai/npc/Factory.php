<?php

namespace Cartographica\AI;

/*
The Archetype Factory (NPC/Factory.php)
This is where you define your unit types. Adding a new NPC type is now just a single case in a switch
or a config file.
*/

class ArchetypeFactory {
    public static function create(string $type): Agent {
        return match ($type) {
            'scout' => new Agent(4, 3, [
                'aggressiveness' => 0.1,
                'expansionist'   => 0.9,
                'curiosity'      => 0.8,
                'caution'        => 0.4
            ]),
            'warrior' => new Agent(4, 3, [
                'aggressiveness' => 0.9,
                'expansionist'   => 0.2,
                'curiosity'      => 0.1,
                'caution'        => 0.6
            ]),
            'colonist' => new Agent(4, 3, [
                'aggressiveness' => 0.2,
                'expansionist'   => 0.7,
                'curiosity'      => 0.3,
                'caution'        => 0.3
            ]),
            default => new Agent(4, 3, ['default' => 0.5])
        };
    }
}
