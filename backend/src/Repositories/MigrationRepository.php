<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MigrationRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    /**
     * Get all applied migrations
     */
    public function getAppliedMigrations(): array
    {
        $this->ensureMigrationsTable();
        
        $stmt = $this->db->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Mark a migration as applied
     */
    public function markApplied(string $name): void
    {
        $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (:name)");
        $stmt->execute(['name' => $name]);
    }

    /**
     * Run pending migrations and return result
     */
    public function runPendingMigrations(): array
    {
        $this->ensureMigrationsTable();
        
        $appliedMigrations = $this->getAppliedMigrations();
        $migrations = $this->getMigrations();
        
        $applied = [];
        foreach ($migrations as $name => $sql) {
            if (!in_array($name, $appliedMigrations)) {
                try {
                    $this->db->exec($sql);
                    $this->markApplied($name);
                    $applied[] = $name;
                } catch (\PDOException $e) {
                    // Check for "Column already exists" (1060/42S21) or "Duplicate key name" (1061)
                    // Also check for "Unknown column" (1054/42S22) - e.g. trying to modify 'type' if it doesn't exist
                    $msg = $e->getMessage();
                    if (str_contains($msg, '1060') || str_contains($msg, '1061') || str_contains($msg, 'Column already exists')) {
                        $this->markApplied($name);
                        $applied[] = $name . ' (skipped - already exists)';
                    } elseif (str_contains($msg, '1054') || str_contains($msg, "Unknown column")) {
                         $this->markApplied($name);
                         $applied[] = $name . ' (skipped - column missing)';
                    } else {
                        throw $e;
                    }
                }
            }
        }

        return [
            'applied' => $applied,
            'total_migrations' => count($migrations),
            'already_applied' => count($appliedMigrations)
        ];
    }

    /**
     * Ensure migrations table exists
     */
    private function ensureMigrationsTable(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    /**
     * Get all migration definitions
     */
    private function getMigrations(): array
    {
        return [
            'create_projects_git_table' => "
                CREATE TABLE IF NOT EXISTS projects_git (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    project_id INT NOT NULL UNIQUE,
                    last_updated DATETIME,
                    last_build DATETIME,
                    last_commit_message TEXT,
                    branch VARCHAR(100),
                    git_commit VARCHAR(100),
                    environments JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_project_id (project_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ",
            'create_suggestion_comments_table' => "
                CREATE TABLE IF NOT EXISTS project_suggestion_comments (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    project_suggestion_id BIGINT UNSIGNED NOT NULL,
                    user_id BIGINT UNSIGNED NULL,
                    user_name VARCHAR(255) NULL,
                    content TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_suggestion (project_suggestion_id),
                    INDEX idx_user (user_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'add_user_id_to_suggestions' => "
                ALTER TABLE project_suggestions ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id;
            ",
            'add_user_id_index_to_suggestions' => "
                CREATE INDEX idx_user_id ON project_suggestions(user_id);
            ",
            'add_rationale_to_suggestions' => "
                ALTER TABLE project_suggestions ADD COLUMN rationale TEXT NULL AFTER description;
            ",
            'add_tags_to_suggestions' => "
                ALTER TABLE project_suggestions ADD COLUMN tags VARCHAR(255) NULL;
            ",
            'add_status_to_suggestions' => "
                ALTER TABLE project_suggestions ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'pending';
            ",
            'add_votes_to_suggestions' => "
                ALTER TABLE project_suggestions ADD COLUMN votes INT NOT NULL DEFAULT 0;
            ",
            'add_name_to_suggestions' => "
                ALTER TABLE project_suggestions ADD COLUMN name VARCHAR(255) NOT NULL;
            ",
            'create_activity_feed_table' => "
                CREATE TABLE IF NOT EXISTS activity_feed (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NULL,
                    activity_type VARCHAR(50) NOT NULL,
                    message TEXT NOT NULL,
                    reference_id BIGINT UNSIGNED NULL,
                    reference_type VARCHAR(50) NULL,
                    metadata JSON NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_feed (user_id),
                    INDEX idx_reference (reference_id, reference_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ",
            'add_user_id_to_activity_feed' => "
                ALTER TABLE activity_feed ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id;
            ",
            'add_activity_type_to_activity_feed' => "
                ALTER TABLE activity_feed ADD COLUMN activity_type VARCHAR(50) NOT NULL AFTER user_id;
            ",
            'add_message_to_activity_feed' => "
                ALTER TABLE activity_feed ADD COLUMN message TEXT NOT NULL AFTER activity_type;
            ",
            'add_metadata_to_activity_feed' => "
                ALTER TABLE activity_feed ADD COLUMN metadata JSON NULL;
            ",
            'make_type_nullable_in_activity_feed' => "
                ALTER TABLE activity_feed MODIFY COLUMN type VARCHAR(255) NULL DEFAULT 'info';
            ",
            'make_action_nullable_in_activity_feed' => "
                ALTER TABLE activity_feed MODIFY COLUMN action VARCHAR(255) NULL DEFAULT '';
            ",
            'make_title_nullable_in_activity_feed' => "
                ALTER TABLE activity_feed MODIFY COLUMN title VARCHAR(255) NULL DEFAULT '';
            ",
            'make_description_nullable_in_activity_feed' => "
                ALTER TABLE activity_feed MODIFY COLUMN description TEXT NULL;
            ",
            'make_user_nullable_in_activity_feed' => "
                ALTER TABLE activity_feed MODIFY COLUMN user VARCHAR(255) NULL;
            ",
        ];
    }
}
