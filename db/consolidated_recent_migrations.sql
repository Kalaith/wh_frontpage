-- =====================================================
-- COMPREHENSIVE RECENT MIGRATION SCRIPT
-- =====================================================
-- Includes: Quest Acceptance & Ranks, Guild Master Role, Boss Phase 2
-- Usage: Run this script directly against your database.
-- =====================================================

USE webhatch_frontpage;

-- 1. QUEST ACCEPTANCE & RANK MIGRATION
-- Add rank column to adventurers
ALTER TABLE adventurers
    ADD COLUMN IF NOT EXISTS `rank` ENUM('Iron','Silver','Gold','Jade','Diamond') NOT NULL DEFAULT 'Iron' AFTER `level`;

CREATE INDEX IF NOT EXISTS idx_rank ON adventurers (`rank`);

-- Quest Acceptances (per-quest state per adventurer)
CREATE TABLE IF NOT EXISTS quest_acceptances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    quest_ref VARCHAR(255) NOT NULL,         -- quest code or synthesized ID string
    status ENUM('accepted','submitted','completed','rejected') NOT NULL DEFAULT 'accepted',
    accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    reviewer_adventurer_id BIGINT UNSIGNED NULL,
    review_notes TEXT NULL,
    UNIQUE INDEX idx_adv_quest (adventurer_id, quest_ref),
    INDEX idx_quest_ref (quest_ref),
    INDEX idx_status (status),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_adventurer_id) REFERENCES adventurers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. GUILD MASTER MIGRATION
-- Adds project ownership to support guild master quest posting.
ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS owner_user_id BIGINT UNSIGNED NULL AFTER hidden;

CREATE INDEX IF NOT EXISTS idx_projects_owner_user_id ON projects (owner_user_id);

-- 3. BOSS PHASE 2 MIGRATION
-- Add phase tracking columns to the bosses table
ALTER TABLE bosses 
    ADD COLUMN IF NOT EXISTS phase INT UNSIGNED DEFAULT 1 AFTER threat_level,
    ADD COLUMN IF NOT EXISTS max_phase INT UNSIGNED DEFAULT 1 AFTER phase;

SELECT '✅ Comprehensive Migration script perfectly executed!' as status;
