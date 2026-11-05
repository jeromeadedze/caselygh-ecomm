-- Lets the team choose the home page's Latest designs. Only adds an empty column; safe to run twice.
ALTER TABLE products ADD COLUMN IF NOT EXISTS featured_at DATETIME NULL DEFAULT NULL;
