-- Cleanup: drop tables that are no longer referenced by any code.
--
-- Scope: the shared `frontpage` database, used by exactly two apps:
--   - frontpage/backend
--   - apps/project_roost/backend
--
-- Every table below was verified (2026-07-21) to be:
--   * referenced by NO PHP in either backend (repositories/controllers/services/bin),
--   * empty (0 rows),
--   * not the target of any foreign key, and
--   * not (re)created by the auto-run migrations (db-init.php / sync-projects.php).
--
-- NOTE: `loot_crates` is still defined in frontpage/db/setup_gamification.sql.
-- Re-running that manual script would recreate it. Remove it there too if you
-- want it gone permanently.
--
-- Recommended: take a backup first, e.g.
--   mysqldump -u root -p frontpage feature_approvals game_attributes kofi_integrations loot_crates user_preferences > unused_tables_backup.sql

DROP TABLE IF EXISTS `feature_approvals`;
DROP TABLE IF EXISTS `game_attributes`;
DROP TABLE IF EXISTS `kofi_integrations`;
DROP TABLE IF EXISTS `loot_crates`;
DROP TABLE IF EXISTS `user_preferences`;
