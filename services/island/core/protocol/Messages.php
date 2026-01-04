<?php

namespace cartographica\services\island\core\protocol;

class Messages {
    const HELLO = 'HELLO';
    const AUTH = 'AUTH';
    const AUTH_OK = 'AUTH_OK';
    const REQUEST_WORLD = 'REQUEST_WORLD';
    const WORLD = 'WORLD';
    const MOVE = 'MOVE';
    const CHAT = 'CHAT';

    // Island-to-island
    const ISLAND_HELLO = 'ISLAND_HELLO';
    const ISLAND_WELCOME = 'ISLAND_WELCOME';
    const SYNC_TILES = 'SYNC_TILES';
    const PLAYER_TRANSFER = 'PLAYER_TRANSFER';
    const DISASTER_EVENT = 'DISASTER_EVENT';
}
