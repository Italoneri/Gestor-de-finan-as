-- Categories carry their own colour so the dashboard donut is readable: the
-- chart used to cycle one cool family, which made neighbouring slices alike.
ALTER TABLE categories ADD COLUMN color TEXT NOT NULL DEFAULT '#0ea5e9';

-- Existing rows get the palette spread over them rather than all-blue. The
-- order interleaves warm and cool so categories created back to back, which
-- land on consecutive ids, do not come out as neighbouring hues.
UPDATE categories SET color = CASE id % 10
    WHEN 0 THEN '#0ea5e9'
    WHEN 1 THEN '#f97316'
    WHEN 2 THEN '#6366f1'
    WHEN 3 THEN '#22c55e'
    WHEN 4 THEN '#8b5cf6'
    WHEN 5 THEN '#f59e0b'
    WHEN 6 THEN '#06b6d4'
    WHEN 7 THEN '#f43f5e'
    WHEN 8 THEN '#14b8a6'
    WHEN 9 THEN '#3b82f6'
END;
