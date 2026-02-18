-- =====================================================
-- Quest Acceptance & Rank Migration
-- =====================================================
-- Adds quest acceptance tracking and rank system.
-- Usage: mysql -u username -p webhatchery_frontpage < db/quest_acceptance_migration.sql
-- =====================================================

USE webhatchery_frontpage;

-- 1. Add rank column to adventurers
ALTER TABLE adventurers
    ADD COLUMN IF NOT EXISTS `rank` ENUM('Iron','Silver','Gold','Jade','Diamond') NOT NULL DEFAULT 'Iron' AFTER `level`;

CREATE INDEX IF NOT EXISTS idx_rank ON adventurers (`rank`);

-- 2. Quest Acceptances (per-quest state per adventurer)
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

SELECT '✅ Quest acceptance migration complete!' as status;
