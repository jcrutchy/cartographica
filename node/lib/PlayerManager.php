<?php

class PlayerManager
{
    private SQLite3 $db;
    private array $cache = [];

    public function __construct()
    {
        $this->db = DB::get();
        $this->db->exec(file_get_contents("data/schema.sql"));
    }

    public function load(string $playerId): array
    {
        // Cached?
        if (isset($this->cache[$playerId])) {
            return $this->cache[$playerId];
        }

        $stmt = $this->db->prepare("SELECT data FROM players WHERE player_id = :id");
        $stmt->bindValue(":id", $playerId, SQLITE3_TEXT);
        $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if ($result) {
            $player = json_decode($result["data"], true);
        } else {
            // Create new player
            $player = [
                "player_id" => $playerId,
                "created_at" => time(),
                "last_seen" => time(),
                "position" => [0, 0],
                "inventory" => [],
                "stats" => [
                    "health" => 100,
                    "mana" => 50
                ]
            ];
            $this->save($playerId, $player);
        }

        $this->cache[$playerId] = $player;
        return $player;
    }

    public function save(string $playerId, array $player): void
    {
        $player["last_seen"] = time();
        $json = json_encode($player);

        $stmt = $this->db->prepare("
            INSERT INTO players (player_id, data)
            VALUES (:id, :data)
            ON CONFLICT(player_id) DO UPDATE SET data = excluded.data
        ");

        $stmt->bindValue(":id", $playerId, SQLITE3_TEXT);
        $stmt->bindValue(":data", $json, SQLITE3_TEXT);
        $stmt->execute();

        $this->cache[$playerId] = $player;
    }

    public function unload(string $playerId): void
    {
        if (isset($this->cache[$playerId])) {
            $this->save($playerId, $this->cache[$playerId]);
            unset($this->cache[$playerId]);
        }
    }
}
