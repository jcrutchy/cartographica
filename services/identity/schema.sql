CREATE TABLE login_attempts (
    login_attempt_id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    requested_at INTEGER NOT NULL,
    ip_address TEXT NOT NULL,
    player_id TEXT NOT NULL,
    email_token_id TEXT NOT NULL
);

CREATE TABLE session_tokens (
    session_token_id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL,
    issued_at INTEGER NOT NULL,
    expires_at INTEGER NOT NULL,
    player_id TEXT NOT NULL,
    session_token LONGTEXT NOT NULL
);
