-- =====================================================
-- COMPLETE WebHatchery Frontpage Production Database Setup
-- Includes Base Setup, Gamification, and All Recent Migrations
-- Usage: Run this script directly against your database.
-- =====================================================

CREATE DATABASE IF NOT EXISTS webhatch_frontpage 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE webhatch_frontpage;

-- =====================================================
-- 1. BASE TABLES (projects)
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS projects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    path VARCHAR(255) NULL,
    description TEXT NULL,
    stage VARCHAR(50) NOT NULL DEFAULT 'prototype',
    status VARCHAR(50) NOT NULL DEFAULT 'prototype',
    version VARCHAR(20) NOT NULL DEFAULT '0.1.0',
    group_name VARCHAR(50) NOT NULL DEFAULT 'other',
    repository_type VARCHAR(50) NULL,
    repository_url TEXT NULL,
    hidden BOOLEAN NOT NULL DEFAULT FALSE,
    owner_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_group_name (group_name),
    INDEX idx_hidden (hidden),
    INDEX idx_status (status),
    INDEX idx_stage (stage),
    INDEX idx_owner_user_id (owner_user_id),
    INDEX idx_group_hidden (group_name, hidden),
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert project data
INSERT IGNORE INTO projects (id, title, path, description, stage, status, version, group_name, repository_type, repository_url, hidden, created_at, updated_at) VALUES
(1, 'Stories', 'stories/', 'Collection of interactive stories and creative writing projects with immersive web presentations.', 'Static', 'fully-working', '1.2.0', 'fiction', NULL, NULL, FALSE, NOW(), NOW()),
(2, 'Mature Stories', 'storiesx/', 'Story collection that may contain explicit language and adult themes.', 'Static', 'fully-working', '1.1.0', 'fiction', NULL, NULL, FALSE, NOW(), NOW()),
(3, 'Anime Hub', 'anime/', 'Character analysis and development charts for popular anime series including Dragon Ball and Granblue Fantasy.', 'Static', 'fully-working', '1.0.0', 'fiction', NULL, NULL, FALSE, NOW(), NOW()),
(4, 'AI Portrait Gallery', 'gallery/', 'Beautiful AI-generated anime character portrait gallery featuring various animal-themed characters with interactive viewing and modal display capabilities.', 'Static', 'fully-working', '1.0.0', 'fiction', NULL, NULL, FALSE, NOW(), NOW()),
(5, 'Is It Done Yet?', 'apps/isitdoneyet/', 'A full-stack web application for project tracking and status monitoring with real-time updates.', 'Fullstack', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/isitdoneyet', FALSE, NOW(), NOW()),
(6, 'Auth Portal', 'apps/auth/', 'User Authentication Portal for managing user accounts, roles, and permissions with secure login and registration features.', 'Auth', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/auth', FALSE, NOW(), NOW()),
(7, 'Meme Generator', 'apps/meme_generator/', 'Interactive tool for creating custom memes with various templates and text customization options.', 'React', 'MVP', '0.8.0', 'apps', 'git', 'https://github.com/Kalaith/meme_generator', FALSE, NOW(), NOW()),
(8, 'Project Management', 'apps/project_management/', 'Comprehensive project management dashboard with stakeholder tracking and report generation features.', 'Dashboard', 'MVP', '1.0.0', 'apps', NULL, NULL, FALSE, NOW(), NOW()),
(9, 'LitRPG Studio', 'apps/litrpg_studio/frontend/', 'A specialized writing tool designed for LitRPG authors to create and manage game mechanics in their stories.', 'Tool', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/LitrpgStudio', FALSE, NOW(), NOW()),
(10, 'Monster Maker', 'apps/monster_maker/', 'D&D monster creation tool with challenge rating calculator and stat progression charts.', 'Tool', 'MVP', '1.0.0', 'apps', NULL, NULL, FALSE, NOW(), NOW()),
(11, 'Name Generator API', 'apps/name_generator/frontend/', 'Comprehensive name generation tool with multiple algorithms for creating people, place, event, and title names using Markov chains, phonetic patterns, and cultural linguistics.', 'API', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/name_generator', FALSE, NOW(), NOW()),
(12, 'Story Forge', 'apps/story_forge/frontend/', 'Creative writing assistant tool that helps authors develop characters, plotlines, settings, and narrative arcs with an intuitive interface and visualization capabilities.', 'Tool', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/story_forge', FALSE, NOW(), NOW()),
(13, 'Anime Prompt Generator', 'apps/anime_prompt_gen/frontend/', 'React-based anime prompt generator with modular extensibility for animal girls, monster girls, and monsters.', 'Generator', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/PromptGenerator', FALSE, NOW(), NOW()),
(14, 'Campaign Chronicle', 'apps/campaign_chronicle/frontend/', 'D&D Campaign Companion for managing campaigns, characters, locations, items, relationships, and session notes with an intuitive interface and local storage.', 'Companion', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/CampaignChronicle', FALSE, NOW(), NOW()),
(15, 'Quest Generator', 'apps/quest_generator/', 'Fantasy quest generator with comprehensive adventure creation tools for RPG campaigns, featuring multiple quest types and customizable difficulty levels.', 'Generator', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/quest_generator', FALSE, NOW(), NOW()),
(16, 'WH Tracker', 'apps/wh_tracker/frontend/', 'WebHatchery project tracking and monitoring application with real-time status updates and analytics dashboard.', 'React', 'MVP', '0.9.0', 'apps', 'git', 'https://github.com/Kalaith/wh_tracker', FALSE, NOW(), NOW()),
(17, 'WebHatchery Frontpage', 'frontpage/frontend/', 'React version of the main WebHatchery landing page with project portfolio, status badges, and interactive navigation.', 'React', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/wh_frontpage', FALSE, NOW(), NOW()),
(18, 'Dragons Den', 'game_apps/dragons_den/frontend/', 'Fantasy dragon management game with breeding and exploration mechanics.', 'React', 'non-working', '0.3.0', 'games', 'git', 'https://github.com/Kalaith/DragonsDen', FALSE, NOW(), NOW()),
(19, 'Magical Girl', 'game_apps/magical_girl/frontend/', 'Magical girl transformation and adventure game with character progression.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/magical_girl', FALSE, NOW(), NOW()),
(20, 'Xenomorph Park', 'game_apps/xenomorph_park/frontend/', 'Xenomorph Park is a sci-fi park management and survival game built with React. Manage alien creatures, containment systems, and visitor safety in a modern, interactive web app.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/xenomorph_park', FALSE, NOW(), NOW()),
(21, 'Dungeon Core', 'game_apps/dungeon_core/frontend/', 'Dungeon management game with a React frontend, detailed JSON-based monster evolution trees, trait system, unlock conditions, and a comprehensive GDD. Features persistent adventurer parties, mana/trap economy, room themes, monster breeds/evolution, and advanced UI/gameplay systems. All monster, trait, and unlock data is modular and up-to-date.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/dungeon_core', FALSE, NOW(), NOW()),
(22, 'Emoji Tower', 'game_apps/emoji_tower/', 'Casual tower-building puzzle game featuring emoji-based construction elements with physics simulation, progressive difficulty levels, and unlockable special emoji blocks with unique properties.', 'Game', 'prototype', '0.1.0', 'games', NULL, NULL, FALSE, NOW(), NOW()),
(23, 'Planet Trader', 'game_apps/planet_trader/frontend/', 'Sci-fi trading and exploration game where players manage resources, trade between planets, and upgrade their ship in a dynamic universe. Built with a modern React frontend.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/planet_trader', FALSE, NOW(), NOW()),
(24, 'Kingdom Wars', 'game_apps/kingdom_wars/frontend/', 'A comprehensive text-based war game featuring kingdom management, military strategy, resource allocation, and tactical combat with dynamic events and progression systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/kingdom_wars', FALSE, NOW(), NOW()),
(25, 'Adventurer Guild', 'game_apps/adventurer_guild/frontend/', 'React-based adventurer guild management app with quest tracking and character progression.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/adventurer_guild', FALSE, NOW(), NOW()),
(26, 'Kemo Simulator', 'game_apps/kemo_sim/frontend/', 'React-based simulation game featuring animal-themed characters and dynamic interactions.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/kemo_sim', FALSE, NOW(), NOW()),
(27, 'Interstellar Romance', 'game_apps/interstellar_romance/frontend/', 'Sci-fi romance simulation game featuring relationship building, space exploration, and character interactions across different alien civilizations.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/interstellar_romance', FALSE, NOW(), NOW()),
(28, 'Ashes of Aeloria', 'game_apps/ashes_of_aeloria/frontend/', 'High-fantasy node-based strategy game that blends classic commander-driven warfare with modern tactical gameplay. Players control elite commanders across a connected network of strategic nodes, building armies, managing resources, and engaging in large-scale battles to conquer the realm of Aeloria.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/AshesOfAeloria', FALSE, NOW(), NOW()),
(29, 'Mytherra', 'game_apps/mytherra/frontend/', 'Fantasy MMO-inspired game with crafting, exploration, and character progression systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/dawurth/Mytherra', FALSE, NOW(), NOW()),
(30, 'TB Realms', 'game_apps/tb_realms/frontend/', 'Turn-based strategy game with realm management and tactical combat systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/dawurth/stock_management', FALSE, NOW(), NOW()),
(31, 'Blacksmith Forge', 'game_apps/blacksmith_forge/frontend/', 'Fantasy blacksmithing simulation game with crafting mechanics, resource management, and equipment creation systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/BlacksmithForge', FALSE, NOW(), NOW()),
(32, 'Dungeon Master', 'game_apps/dungeon_master/frontend/', 'Reverse RPG experience where players take on the role of a dungeon master, managing monsters, traps, and defending against adventuring parties.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/dungeon_master', FALSE, NOW(), NOW()),
(33, 'Hive Mind', 'game_apps/hive_mind/', 'Strategic idle colony game where players manage a hive mind collective with expanding influence and resource management.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/hive_mind', FALSE, NOW(), NOW()),
(34, 'Infestium', 'game_apps/infestium/frontend/', 'Horror-themed infestation management game with strategic gameplay and survival mechanics.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/infestium', FALSE, NOW(), NOW()),
(35, 'MMO Sandbox', 'game_apps/mmo_sandbox/frontend/', 'Sandbox MMO-style game with world building, player interactions, and persistent universe mechanics.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/mmo_sandbox', FALSE, NOW(), NOW()),
(36, 'Monster Farm', 'game_apps/monster_farm/', 'Monster breeding and farming simulation game with creature collection and management systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/monster_farm', FALSE, NOW(), NOW()),
(37, 'Robot Battler', 'game_apps/robot_battler/', 'Turn-based robot combat game featuring customizable mechs, strategic battles, and progression systems.', 'Game', 'prototype', '0.1.0', 'games', NULL, NULL, FALSE, NOW(), NOW()),
(38, 'SES', 'web/ses/frontend/', 'Session Emulation System', 'Static', 'working', '1.0.0', 'private', NULL, NULL, TRUE, NOW(), NOW()),
(39, 'Kaiju Simulator', 'gdd/kaiju_simulator/', 'Game Design Document for a Kaiju breeding and battle simulator featuring detailed progression systems, combat mechanics, and creature management concepts.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW()),
(40, 'Echo of the Many', 'gdd/echo_of_the_many/', 'Fantasy strategy game where players control a mage who creates magical clones to infiltrate and influence a medieval city\'s political landscape. Features comprehensive React implementation plan with mobile-first PWA design.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW()),
(41, 'Settlement Builder', 'gdd/settlement/', 'Medieval settlement building and management game design document featuring city planning, resource management, and citizen happiness mechanics.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW()),
(42, 'Space Colony Simulator', 'gdd/space_sim/', 'Space colony management simulator with resource management and exploration systems.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW()),
(43, 'Tactics Game', 'gdd/tactics_game/', 'Turn-based tactical combat game with grid-based movement, character classes, and strategic battle mechanics.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW());

-- =====================================================
-- 2. GAMIFICATION TABLES (Adventurers, Quests, Bosses)
-- =====================================================

CREATE TABLE IF NOT EXISTS adventurers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL, -- correlates with users.id
    github_username VARCHAR(100) NOT NULL,
    class VARCHAR(50) DEFAULT 'hatchling',
    spec_primary VARCHAR(50) NULL,
    spec_secondary VARCHAR(50) NULL,
    xp_total INT UNSIGNED DEFAULT 0,
    level INT UNSIGNED DEFAULT 1,
    `rank` ENUM('Iron','Silver','Gold','Jade','Diamond') NOT NULL DEFAULT 'Iron',
    equipped_title VARCHAR(100) NULL,
    glow_streak INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_github_user (github_username),
    INDEX idx_level (level),
    INDEX idx_xp (xp_total),
    INDEX idx_rank (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS adventurer_badges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    badge_slug VARCHAR(100) NOT NULL,
    badge_name VARCHAR(255) NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_adventurer_badge (adventurer_id, badge_slug),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS habitat_mastery (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    mastery_level INT UNSIGNED DEFAULT 0,
    contributions INT UNSIGNED DEFAULT 0,
    reviews INT UNSIGNED DEFAULT 0,
    last_contribution_at TIMESTAMP NULL,
    UNIQUE INDEX idx_adv_project (adventurer_id, project_id),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS xp_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    amount INT NOT NULL,
    source_type ENUM('quest','boss','review','bonus','streak','crate') NOT NULL,
    source_ref VARCHAR(255) NULL,
    project_id BIGINT UNSIGNED NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_adventurer_time (adventurer_id, created_at),
    FOREIGN KEY (adventurer_id) REFERENCES adventurers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS bosses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    github_issue_url VARCHAR(255) NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT NULL,
    threat_level TINYINT UNSIGNED DEFAULT 3,
    phase INT UNSIGNED DEFAULT 1,
    max_phase INT UNSIGNED DEFAULT 1,
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

CREATE TABLE IF NOT EXISTS quest_acceptances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adventurer_id BIGINT UNSIGNED NOT NULL,
    quest_ref VARCHAR(255) NOT NULL,
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

INSERT IGNORE INTO seasons (name, slug, starts_at, ends_at, is_active)
VALUES ('Season 1: The Awakening', 'season-1', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 WEEK), TRUE);

-- =====================================================
-- 3. APPLY NEW COLUMNS (in case the tables already existed without them)
-- =====================================================
ALTER TABLE adventurers
    ADD COLUMN IF NOT EXISTS `rank` ENUM('Iron','Silver','Gold','Jade','Diamond') NOT NULL DEFAULT 'Iron' AFTER `level`;

ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS owner_user_id BIGINT UNSIGNED NULL AFTER hidden;

ALTER TABLE bosses 
    ADD COLUMN IF NOT EXISTS phase INT UNSIGNED DEFAULT 1 AFTER threat_level,
    ADD COLUMN IF NOT EXISTS max_phase INT UNSIGNED DEFAULT 1 AFTER phase;

SET FOREIGN_KEY_CHECKS = 1;

SELECT '✅ Full Initialization & Migration script perfectly executed!' as status;
