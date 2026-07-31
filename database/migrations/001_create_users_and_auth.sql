CREATE TABLE users (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    name              TEXT NOT NULL,
    email             TEXT NOT NULL UNIQUE,
    password_hash     TEXT NOT NULL,
    email_verified_at TEXT NULL,
    created_at        TEXT NOT NULL,
    updated_at        TEXT NOT NULL
);

-- brute-force protection: attempts counted per e-mail and per IP inside a time window
CREATE TABLE login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    email        TEXT NOT NULL,
    ip           TEXT NOT NULL,
    attempted_at TEXT NOT NULL
);
CREATE INDEX idx_attempts_email ON login_attempts(email, attempted_at);
CREATE INDEX idx_attempts_ip    ON login_attempts(ip, attempted_at);

-- remember-me: selector is the lookup key, validator is stored only as a hash
CREATE TABLE remember_tokens (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id        INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    selector       TEXT NOT NULL UNIQUE,
    validator_hash TEXT NOT NULL,
    expires_at     TEXT NOT NULL,
    created_at     TEXT NOT NULL
);

CREATE TABLE password_resets (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE email_verifications (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    created_at TEXT NOT NULL
);
