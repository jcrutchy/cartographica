CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    player_id TEXT NOT NULL UNIQUE,
    created_at INTEGER NOT NULL
);

CREATE TABLE login_links (
    token TEXT PRIMARY KEY,
    email TEXT NOT NULL,
    expires_at INTEGER NOT NULL,
    used INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE device_tokens (
    token TEXT PRIMARY KEY,
    user_id INTEGER NOT NULL,
    issued_at INTEGER NOT NULL,
    expires_at INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
