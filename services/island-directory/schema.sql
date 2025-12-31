CREATE TABLE islands (
    island_id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    owner_email TEXT NOT NULL,
    public_key TEXT NOT NULL,
    certificate TEXT NOT NULL,
    issued_at INTEGER NOT NULL,
    expires_at INTEGER NOT NULL,
    metadata TEXT
);
