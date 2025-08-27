-- =====================================================
-- WebHatchery Frontpage Production Database Setup
-- =====================================================
-- Complete SQL script to set up the production database
-- for WebHatchery frontpage application
--
-- Usage: 
--   mysql -u username -p database_name < setup.sql
-- 
-- Or for first-time setup with database creation:
--   mysql -u root -p < setup.sql
-- =====================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS webhatchery_frontpage 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE webhatchery_frontpage;

-- =====================================================
-- Drop existing tables (for clean setup)
-- =====================================================
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS projects;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Create projects table
-- =====================================================

CREATE TABLE projects (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes for performance
    INDEX idx_group_name (group_name),
    INDEX idx_hidden (hidden),
    INDEX idx_status (status),
    INDEX idx_stage (stage),
    INDEX idx_group_hidden (group_name, hidden),
    INDEX idx_created_at (created_at),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Insert project data
-- =====================================================

-- Fiction Projects
INSERT INTO projects (title, path, description, stage, status, version, group_name, repository_type, repository_url, hidden, created_at, updated_at) VALUES
('Stories', 'stories/', 'Collection of interactive stories and creative writing projects with immersive web presentations.', 'Static', 'fully-working', '1.2.0', 'fiction', NULL, NULL, FALSE, NOW(), NOW()),
('Mature Stories', 'storiesx/', 'Story collection that may contain explicit language and adult themes.', 'Static', 'fully-working', '1.1.0', 'fiction', NULL, NULL, FALSE, NOW(), NOW()),
('Anime Hub', 'anime/', 'Character analysis and development charts for popular anime series including Dragon Ball and Granblue Fantasy.', 'Static', 'fully-working', '1.0.0', 'fiction', NULL, NULL, FALSE, NOW(), NOW()),
('AI Portrait Gallery', 'gallery/', 'Beautiful AI-generated anime character portrait gallery featuring various animal-themed characters with interactive viewing and modal display capabilities.', 'Static', 'fully-working', '1.0.0', 'fiction', NULL, NULL, FALSE, NOW(), NOW());

-- Web Applications
INSERT INTO projects (title, path, description, stage, status, version, group_name, repository_type, repository_url, hidden, created_at, updated_at) VALUES
('Is It Done Yet?', 'apps/isitdoneyet/', 'A full-stack web application for project tracking and status monitoring with real-time updates.', 'Fullstack', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/isitdoneyet', FALSE, NOW(), NOW()),
('Auth Portal', 'apps/auth/', 'User Authentication Portal for managing user accounts, roles, and permissions with secure login and registration features.', 'Auth', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/auth', FALSE, NOW(), NOW()),
('Meme Generator', 'apps/meme_generator/', 'Interactive tool for creating custom memes with various templates and text customization options.', 'React', 'MVP', '0.8.0', 'apps', 'git', 'https://github.com/Kalaith/meme_generator', FALSE, NOW(), NOW()),
('Project Management', 'apps/project_management/', 'Comprehensive project management dashboard with stakeholder tracking and report generation features.', 'Dashboard', 'MVP', '1.0.0', 'apps', NULL, NULL, FALSE, NOW(), NOW()),
('LitRPG Studio', 'apps/litrpg_studio/frontend/', 'A specialized writing tool designed for LitRPG authors to create and manage game mechanics in their stories.', 'Tool', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/LitrpgStudio', FALSE, NOW(), NOW()),
('Monster Maker', 'apps/monster_maker/', 'D&D monster creation tool with challenge rating calculator and stat progression charts.', 'Tool', 'MVP', '1.0.0', 'apps', NULL, NULL, FALSE, NOW(), NOW()),
('Name Generator API', 'apps/name_generator/frontend/', 'Comprehensive name generation tool with multiple algorithms for creating people, place, event, and title names using Markov chains, phonetic patterns, and cultural linguistics.', 'API', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/name_generator', FALSE, NOW(), NOW()),
('Story Forge', 'apps/story_forge/frontend/', 'Creative writing assistant tool that helps authors develop characters, plotlines, settings, and narrative arcs with an intuitive interface and visualization capabilities.', 'Tool', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/story_forge', FALSE, NOW(), NOW()),
('Anime Prompt Generator', 'apps/anime_prompt_gen/frontend/', 'React-based anime prompt generator with modular extensibility for animal girls, monster girls, and monsters.', 'Generator', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/PromptGenerator', FALSE, NOW(), NOW()),
('Campaign Chronicle', 'apps/campaign_chronicle/frontend/', 'D&D Campaign Companion for managing campaigns, characters, locations, items, relationships, and session notes with an intuitive interface and local storage.', 'Companion', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/CampaignChronicle', FALSE, NOW(), NOW()),
('Quest Generator', 'apps/quest_generator/', 'Fantasy quest generator with comprehensive adventure creation tools for RPG campaigns, featuring multiple quest types and customizable difficulty levels.', 'Generator', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/quest_generator', FALSE, NOW(), NOW()),
('WH Tracker', 'apps/wh_tracker/frontend/', 'WebHatchery project tracking and monitoring application with real-time status updates and analytics dashboard.', 'React', 'MVP', '0.9.0', 'apps', 'git', 'https://github.com/Kalaith/wh_tracker', FALSE, NOW(), NOW()),
('WebHatchery Frontpage', 'frontpage/frontend/', 'React version of the main WebHatchery landing page with project portfolio, status badges, and interactive navigation.', 'React', 'MVP', '1.0.0', 'apps', 'git', 'https://github.com/Kalaith/wh_frontpage', FALSE, NOW(), NOW());

-- Games & Game Design
INSERT INTO projects (title, path, description, stage, status, version, group_name, repository_type, repository_url, hidden, created_at, updated_at) VALUES
('Dragons Den', 'game_apps/dragons_den/frontend/', 'Fantasy dragon management game with breeding and exploration mechanics.', 'React', 'non-working', '0.3.0', 'games', 'git', 'https://github.com/Kalaith/DragonsDen', FALSE, NOW(), NOW()),
('Magical Girl', 'game_apps/magical_girl/frontend/', 'Magical girl transformation and adventure game with character progression.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/magical_girl', FALSE, NOW(), NOW()),
('Xenomorph Park', 'game_apps/xenomorph_park/frontend/', 'Xenomorph Park is a sci-fi park management and survival game built with React. Manage alien creatures, containment systems, and visitor safety in a modern, interactive web app.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/xenomorph_park', FALSE, NOW(), NOW()),
('Dungeon Core', 'game_apps/dungeon_core/frontend/', 'Dungeon management game with a React frontend, detailed JSON-based monster evolution trees, trait system, unlock conditions, and a comprehensive GDD. Features persistent adventurer parties, mana/trap economy, room themes, monster breeds/evolution, and advanced UI/gameplay systems. All monster, trait, and unlock data is modular and up-to-date.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/dungeon_core', FALSE, NOW(), NOW()),
('Emoji Tower', 'game_apps/emoji_tower/', 'Casual tower-building puzzle game featuring emoji-based construction elements with physics simulation, progressive difficulty levels, and unlockable special emoji blocks with unique properties.', 'Game', 'prototype', '0.1.0', 'games', NULL, NULL, FALSE, NOW(), NOW()),
('Planet Trader', 'game_apps/planet_trader/frontend/', 'Sci-fi trading and exploration game where players manage resources, trade between planets, and upgrade their ship in a dynamic universe. Built with a modern React frontend.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/planet_trader', FALSE, NOW(), NOW()),
('Kingdom Wars', 'game_apps/kingdom_wars/frontend/', 'A comprehensive text-based war game featuring kingdom management, military strategy, resource allocation, and tactical combat with dynamic events and progression systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/kingdom_wars', FALSE, NOW(), NOW()),
('Adventurer Guild', 'game_apps/adventurer_guild/frontend/', 'React-based adventurer guild management app with quest tracking and character progression.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/adventurer_guild', FALSE, NOW(), NOW()),
('Kemo Simulator', 'game_apps/kemo_sim/frontend/', 'React-based simulation game featuring animal-themed characters and dynamic interactions.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/kemo_sim', FALSE, NOW(), NOW()),
('Interstellar Romance', 'game_apps/interstellar_romance/frontend/', 'Sci-fi romance simulation game featuring relationship building, space exploration, and character interactions across different alien civilizations.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/interstellar_romance', FALSE, NOW(), NOW()),
('Ashes of Aeloria', 'game_apps/ashes_of_aeloria/frontend/', 'High-fantasy node-based strategy game that blends classic commander-driven warfare with modern tactical gameplay. Players control elite commanders across a connected network of strategic nodes, building armies, managing resources, and engaging in large-scale battles to conquer the realm of Aeloria.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/AshesOfAeloria', FALSE, NOW(), NOW()),
('Mytherra', 'game_apps/mytherra/frontend/', 'Fantasy MMO-inspired game with crafting, exploration, and character progression systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/dawurth/Mytherra', FALSE, NOW(), NOW()),
('TB Realms', 'game_apps/tb_realms/frontend/', 'Turn-based strategy game with realm management and tactical combat systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/dawurth/stock_management', FALSE, NOW(), NOW()),
('Blacksmith Forge', 'game_apps/blacksmith_forge/frontend/', 'Fantasy blacksmithing simulation game with crafting mechanics, resource management, and equipment creation systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/BlacksmithForge', FALSE, NOW(), NOW()),
('Dungeon Master', 'game_apps/dungeon_master/frontend/', 'Reverse RPG experience where players take on the role of a dungeon master, managing monsters, traps, and defending against adventuring parties.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/dungeon_master', FALSE, NOW(), NOW()),
('Hive Mind', 'game_apps/hive_mind/', 'Strategic idle colony game where players manage a hive mind collective with expanding influence and resource management.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/hive_mind', FALSE, NOW(), NOW()),
('Infestium', 'game_apps/infestium/frontend/', 'Horror-themed infestation management game with strategic gameplay and survival mechanics.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/infestium', FALSE, NOW(), NOW()),
('MMO Sandbox', 'game_apps/mmo_sandbox/frontend/', 'Sandbox MMO-style game with world building, player interactions, and persistent universe mechanics.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/mmo_sandbox', FALSE, NOW(), NOW()),
('Monster Farm', 'game_apps/monster_farm/', 'Monster breeding and farming simulation game with creature collection and management systems.', 'Game', 'prototype', '0.1.0', 'games', 'git', 'https://github.com/Kalaith/monster_farm', FALSE, NOW(), NOW()),
('Robot Battler', 'game_apps/robot_battler/', 'Turn-based robot combat game featuring customizable mechs, strategic battles, and progression systems.', 'Game', 'prototype', '0.1.0', 'games', NULL, NULL, FALSE, NOW(), NOW());

-- Private Projects (hidden)
INSERT INTO projects (title, path, description, stage, status, version, group_name, repository_type, repository_url, hidden, created_at, updated_at) VALUES
('SES', 'web/ses/frontend/', 'Session Emulation System', 'Static', 'working', '1.0.0', 'private', NULL, NULL, TRUE, NOW(), NOW());

-- Game Design Documents
INSERT INTO projects (title, path, description, stage, status, version, group_name, repository_type, repository_url, hidden, created_at, updated_at) VALUES
('Kaiju Simulator', 'gdd/kaiju_simulator/', 'Game Design Document for a Kaiju breeding and battle simulator featuring detailed progression systems, combat mechanics, and creature management concepts.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW()),
('Echo of the Many', 'gdd/echo_of_the_many/', 'Fantasy strategy game where players control a mage who creates magical clones to infiltrate and influence a medieval city\'s political landscape. Features comprehensive React implementation plan with mobile-first PWA design.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW()),
('Settlement Builder', 'gdd/settlement/', 'Medieval settlement building and management game design document featuring city planning, resource management, and citizen happiness mechanics.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW()),
('Space Colony Simulator', 'gdd/space_sim/', 'Space colony management simulator with resource management and exploration systems.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW()),
('Tactics Game', 'gdd/tactics_game/', 'Turn-based tactical combat game with grid-based movement, character classes, and strategic battle mechanics.', 'Design', 'planning', '1.0.0', 'game_design', NULL, NULL, FALSE, NOW(), NOW());

-- =====================================================
-- Create a database user for the application (optional)
-- =====================================================
-- Uncomment and modify the following lines if you want to create a dedicated database user

-- CREATE USER IF NOT EXISTS 'webhatchery_app'@'localhost' IDENTIFIED BY 'your_secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON webhatchery_frontpage.* TO 'webhatchery_app'@'localhost';
-- FLUSH PRIVILEGES;

-- =====================================================
-- Verification and setup completion
-- =====================================================

-- Show database info
SELECT 
    '🚀 WebHatchery Frontpage database setup completed successfully!' as status,
    DATABASE() as database_name,
    COUNT(*) as total_projects,
    SUM(CASE WHEN hidden = 0 THEN 1 ELSE 0 END) as visible_projects,
    SUM(CASE WHEN hidden = 1 THEN 1 ELSE 0 END) as hidden_projects
FROM projects;

-- Show projects by group
SELECT 
    group_name,
    COUNT(*) as count,
    SUM(CASE WHEN hidden = 1 THEN 1 ELSE 0 END) as hidden_count
FROM projects 
GROUP BY group_name 
ORDER BY 
    CASE group_name
        WHEN 'fiction' THEN 1
        WHEN 'apps' THEN 2
        WHEN 'games' THEN 3
        WHEN 'game_design' THEN 4
        WHEN 'private' THEN 5
        ELSE 6
    END;

-- =====================================================
-- Performance and maintenance settings
-- =====================================================

-- Analyze table for query optimization
ANALYZE TABLE projects;

-- =====================================================
-- End of setup script
-- =====================================================