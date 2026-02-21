USE webhatchery_frontpage;

-- Add phase tracking columns to the bosses table
ALTER TABLE bosses 
    ADD COLUMN phase INT UNSIGNED DEFAULT 1 AFTER threat_level,
    ADD COLUMN max_phase INT UNSIGNED DEFAULT 1 AFTER phase;
