CREATE TABLE islands (
    island_id INTEGER PRIMARY KEY AUTOINCREMENT,
    island_name TEXT NOT NULL,
    owner_email TEXT NOT NULL,
    public_key LONGTEXT NOT NULL,
    certificate LONGTEXT NOT NULL,
    issued_at INTEGER NOT NULL,
    expires_at INTEGER NOT NULL,
    island_key LONGTEXT
    metadata LONGTEXT
);
