-- =====================================================
-- Guild Master Role + Project Ownership Migration
-- =====================================================
-- Adds project ownership to support guild master quest posting.
-- Usage: mysql -u username -p webhatchery_frontpage < db/guild_master_migration.sql
-- =====================================================

USE webhatchery_frontpage;

ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS owner_user_id BIGINT UNSIGNED NULL AFTER hidden;

CREATE INDEX IF NOT EXISTS idx_projects_owner_user_id ON projects (owner_user_id);

SELECT 'Guild master migration complete' AS status;
