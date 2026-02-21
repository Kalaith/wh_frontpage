-- =====================================================
-- Weekly Heists Migration
-- =====================================================
-- Adds the weekly_heists table to serve dynamic heist data to the frontend
-- =====================================================

USE webhatch_frontpage;

CREATE TABLE IF NOT EXISTS weekly_heists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goal VARCHAR(255) NOT NULL,
    target INT UNSIGNED NOT NULL DEFAULT 10,
    current INT UNSIGNED NOT NULL DEFAULT 0,
    participants INT UNSIGNED NOT NULL DEFAULT 0,
    reward VARCHAR(255) NOT NULL,
    starts_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ends_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default active heist
INSERT IGNORE INTO weekly_heists (goal, target, current, participants, reward, starts_at, ends_at, is_active)
VALUES (
    'Complete 10 Quests',
    10,
    0,
    1,
    '500 XP Bonus + "Heist Champion" Title for all participants',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    TRUE
);

SELECT '✅ Weekly Heists Migration Executed successfully!' as status;
