<?php

namespace cartographica\services\island\world\state;

class PlayerState {
    public string $id;
    public string $name;
    public int $x = 5;
    public int $y = 5;

    public function __construct(array $payload) {
        $this->id = $payload['player_id'] ?? 'guest';
        $this->name = $payload['email'] ?? 'Unknown';
    }

    public function export(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'x' => $this->x,
            'y' => $this->y
        ];
    }
}
