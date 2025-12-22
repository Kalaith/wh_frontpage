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
                $this->db->exec($sql);
                $this->markApplied($name);
                $applied[] = $name;
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
        ];
    }
}
