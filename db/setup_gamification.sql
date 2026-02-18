-- =====================================================
-- WebHatchery Gamification Schema Extension
-- =====================================================
-- Adds RPG tables to the existing webhatchery_frontpage database.
-- Run this script to enable gamification features.
-- Usage: mysql -u username -p webhatchery_frontpage < db/setup_gamification.sql
-- =====================================================

USE webhatchery_frontpage;

-- 1. Adventurers (Player Profiles)
-- Links to existing users table if present, or stands alone for now
CREATE TABLE IF NOT EXISTS adventurers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL, -- correlates with users.id
    github_username VARCHAR(100) NOT NULL,
    class VARCHAR(50) DEFAULT 'hatchling',
    spec_primary VARCHAR(50) NULL,
    spec_secondary VARCHAR(50) NULL,
    xp_total INT UNSIGNED DEFAULT 0,
    level INT UNSIGNED DEFAULT 1,
    equipped_title VARCHAR(100) NULL,
    glow_streak INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_github_user (github_username),
    INDEX idx_level (level),
    INDEX idx_xp (xp_total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Badges (Collection)
CREATE TABLE IF NOT EXISTS adventurer_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    badge_slug VARCHAR(100) NOT NULL,
    badge_name VARCHAR(255) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_adventurer_badge (adventurer_id, badge_slug),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Habitat Mastery (Per-project levels)
CREATE TABLE IF NOT EXISTS habitat_mastery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL, -- references projects.id
    mastery_level INT UNSIGNED DEFAULT 0,
    contributions INT UNSIGNED DEFAULT 0,
    reviews INT UNSIGNED DEFAULT 0,
    last_contribution_at TIMESTAMP NULL,
    UNIQUE INDEX idx_adv_project (adventurer_id, project_id),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. XP Ledger (Transaction History)
CREATE TABLE IF NOT EXISTS xp_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    source_type ENUM('quest','boss','review','bonus','streak','crate') NOT NULL,
    source_ref VARCHAR(255) NULL,   -- e.g., "Kalaith/wh_frontpage#123"
    project_id BIGINT UNSIGNED NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_adventurer_time (adventurer_id, created_at),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Seasons
CREATE TABLE IF NOT EXISTS seasons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    starts_at DATE NOT NULL,
    ends_at DATE NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    path_chosen ENUM('stability','feature') NULL,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Boss Battles
CREATE TABLE IF NOT EXISTS bosses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    github_issue_url VARCHAR(255) NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    threat_level TINYINT UNSIGNED DEFAULT 3,
    status ENUM('active','stabilizing','defeated') DEFAULT 'active',
    project_id BIGINT UNSIGNED NULL,
    season_id BIGINT UNSIGNED NULL,
    hp_total INT UNSIGNED DEFAULT 5000,
    hp_current INT UNSIGNED DEFAULT 5000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    defeated_at TIMESTAMP NULL,
    INDEX idx_status (status),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Loot Crates
CREATE TABLE IF NOT EXISTS loot_crates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    rarity ENUM('common','uncommon','rare','epic','legendary') DEFAULT 'common',
    status ENUM('unopened','opened') DEFAULT 'unopened',
    source VARCHAR(255) NULL,
    contents JSON NULL,
    opened_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_adventurer_status (adventurer_id, status),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Perk Tokens (equippable buffs)
CREATE TABLE IF NOT EXISTS perk_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    perk_slug VARCHAR(100) NOT NULL,
    perk_name VARCHAR(255) NOT NULL,
    perk_effect TEXT NULL,
    is_equipped BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_adventurer_equipped (adventurer_id, is_equipped),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Quest Chains (multi-step storylines)
CREATE TABLE IF NOT EXISTS quest_chains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    steps JSON NOT NULL,
    total_steps INT UNSIGNED NOT NULL DEFAULT 1,
    reward_xp INT UNSIGNED DEFAULT 0,
    reward_badge_slug VARCHAR(100) NULL,
    reward_title VARCHAR(100) NULL,
    season_id BIGINT UNSIGNED NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Quest Chain Progress (per adventurer)
CREATE TABLE IF NOT EXISTS quest_chain_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    chain_id BIGINT UNSIGNED NOT NULL,
    current_step INT UNSIGNED DEFAULT 0,
    status ENUM('active','completed','abandoned') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    UNIQUE INDEX idx_adv_chain (adventurer_id, chain_id),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE,
    FOREIGN KEY (chain_id) REFERENCES quest_chains(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Season 1
INSERT IGNORE INTO seasons (name, slug, starts_at, ends_at, is_active)
VALUES ('Season 1: The Awakening', 'season-1', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 WEEK), TRUE);

SELECT '🚀 Gamification tables created successfully!' as status;

