CREATE TABLE login_attempts (
    login_attempt_id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    requested_at INTEGER NOT NULL,
    ip_address TEXT
);

CREATE TABLE device_tokens (
    device_token_id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    issued_at INTEGER NOT NULL,
    expires_at INTEGER NOT NULL,
    token TEXT NOT NULL
);
