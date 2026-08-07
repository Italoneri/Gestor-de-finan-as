-- Monthly target of money coming in for one income category — the mirror of a
-- ceiling. Kept in its own table because target_cents is not a limit and the
-- two are never queried together.
CREATE TABLE income_goals (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    category_id  INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    month        TEXT NOT NULL,
    target_cents INTEGER NOT NULL CHECK (target_cents > 0),
    UNIQUE (user_id, category_id, month)
);
