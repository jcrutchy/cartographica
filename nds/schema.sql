CREATE TABLE IF NOT EXISTS networks (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT,
    created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS nodes (
    node_id TEXT PRIMARY KEY,
    network_id TEXT NOT NULL,
    ws_url TEXT NOT NULL,
    seed INTEGER NOT NULL,
    connections TEXT NOT NULL, -- JSON array
    status TEXT NOT NULL,
    registered_at INTEGER NOT NULL,
    updated_at INTEGER NOT NULL,
    FOREIGN KEY (network_id) REFERENCES networks(id)
);
