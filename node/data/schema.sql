-- Player table
CREATE TABLE IF NOT EXISTS players (
    player_id TEXT PRIMARY KEY,
    data      TEXT NOT NULL,
    created_at INTEGER NOT NULL DEFAULT (strftime('%s','now')),
    updated_at INTEGER NOT NULL DEFAULT (strftime('%s','now'))
);

-- Optional: world state key/value store
CREATE TABLE IF NOT EXISTS world_state (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL
);

-- Optional: chunk storage (for future world system)
CREATE TABLE IF NOT EXISTS chunks (
    chunk_x INTEGER NOT NULL,
    chunk_y INTEGER NOT NULL,
    data    BLOB NOT NULL,
    PRIMARY KEY (chunk_x, chunk_y)
);
