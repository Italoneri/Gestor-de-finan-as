-- "Meta" was the wrong word for a spending limit: a goal is something you aim
-- to reach, a limit is something you aim not to cross. The limit becomes a
-- "teto" (ceiling) and the name "meta" is freed for income targets (005).
-- Nothing references budgets by foreign key, so the rename carries its rows,
-- its UNIQUE (user_id, category_id, month) and its index along untouched.
ALTER TABLE budgets RENAME TO ceilings;
