<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProjectRepository
{
    private ?bool $projectRoostProfilesAvailable = null;
    private ?bool $projectRoostArchivedAvailable = null;

    public function __construct(
        private readonly PDO $db
    ) {}

    /**
     * All projects that project_roost knows about (has a profile for).
     * project_roost is the single source of truth, so this requires a profile.
     */
    public function all(): array
    {
        $stmt = $this->db->query($this->selectProjectsSql(
            orderBy: 'ORDER BY p.group_name, p.title',
            requireProfile: true
        ));
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare($this->selectProjectsSql('WHERE p.id = :id LIMIT 1'));
        $stmt->execute(['id' => $id]);
        $project = $stmt->fetch();
        
        return $project ?: null;
    }

    /**
     * Projects for the public homepage: project_roost catalog entries that are
     * not hidden. Includes archived projects (the caller separates them into the
     * archive section) plus anything flagged show_on_homepage.
     */
    public function getHomepageProjects(): array
    {
        $archivedExpr = $this->archivedExpr();
        $stmt = $this->db->query($this->selectProjectsSql(
            "WHERE COALESCE(p.hidden, 0) = 0 AND ({$archivedExpr} = 1 OR p.show_on_homepage = 1)",
            'ORDER BY p.group_name, p.title',
            requireProfile: true
        ));
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO projects (title, path, description, stage, status, version, group_name, repository_type, repository_url, show_on_homepage, owner_user_id) 
             VALUES (:title, :path, :description, :stage, :status, :version, :group_name, :repository_type, :repository_url, :show_on_homepage, :owner_user_id)'
        );
        $stmt->execute([
            'title' => $data['title'],
            'path' => $data['path'] ?? null,
            'description' => $data['description'] ?? null,
            'stage' => $data['stage'] ?? 'prototype',
            'status' => $data['status'] ?? 'prototype',
            'version' => $data['version'] ?? '0.1.0',
            'group_name' => $data['group_name'] ?? 'other',
            'repository_type' => $data['repository_type'] ?? null,
            'repository_url' => $data['repository_url'] ?? null,
            'show_on_homepage' => (int)($data['show_on_homepage'] ?? true),
            'owner_user_id' => isset($data['owner_user_id']) ? (int)$data['owner_user_id'] : null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        // Normalize nested repository payload from frontend forms.
        if (isset($data['repository']) && is_array($data['repository'])) {
            if (array_key_exists('url', $data['repository'])) {
                $data['repository_url'] = $data['repository']['url'];
            }
            if (array_key_exists('type', $data['repository'])) {
                $data['repository_type'] = $data['repository']['type'];
            }
        }

        $fields = [];
        $params = ['id' => $id];

        $allowedFields = [
            'title', 'path', 'description', 'stage', 'status', 'version', 
            'group_name', 'repository_type', 'repository_url', 'show_on_homepage',
            'owner_user_id'
        ];

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowedFields, true)) {
                continue;
            }

            if ($key === 'show_on_homepage') {
                $normalized = $this->normalizeHomepageFlag($value);
                if ($normalized === null) {
                    continue;
                }
                $fields[] = "$key = :$key";
                $params[$key] = $normalized;
                continue;
            }

            if ($key === 'owner_user_id') {
                $fields[] = "$key = :$key";
                $params[$key] = $this->normalizeNullableInt($value);
                continue;
            }

            $fields[] = "$key = :$key";
            $params[$key] = $value;
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE projects SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    private function normalizeHomepageFlag(mixed $value): ?int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_int($value)) {
            return $value === 0 ? 0 : 1;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }

            if (is_numeric($trimmed)) {
                return ((int)$trimmed) === 0 ? 0 : 1;
            }

            $lower = strtolower($trimmed);
            if ($lower === 'true' || $lower === 'yes' || $lower === 'on') {
                return 1;
            }
            if ($lower === 'false' || $lower === 'no' || $lower === 'off') {
                return 0;
            }
        }

        return null;
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return (int)$value;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM projects WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function findByTitle(string $title): ?array
    {
        $stmt = $this->db->prepare($this->selectProjectsSql('WHERE p.title = :title LIMIT 1'));
        $stmt->execute(['title' => $title]);
        $project = $stmt->fetch();
        
        return $project ?: null;
    }

    public function findByPathLike(string $projectName): ?array
    {
        // Try exact path match first, then suffix match
        $stmt = $this->db->prepare($this->selectProjectsSql('WHERE p.path = :exactPath OR p.path LIKE :suffixPath LIMIT 1'));
        $stmt->execute([
            'exactPath' => $projectName,
            'suffixPath' => "%/$projectName"
        ]);
        $project = $stmt->fetch();
        
        return $project ?: null;
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM projects');
        return (int)$stmt->fetchColumn();
    }

    public function assignOwner(int $projectId, ?int $ownerUserId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE projects SET owner_user_id = :owner_user_id, updated_at = NOW() WHERE id = :id'
        );
        return $stmt->execute([
            'id' => $projectId,
            'owner_user_id' => $ownerUserId,
        ]);
    }

    public function isOwnedBy(int $projectId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM projects WHERE id = :id AND owner_user_id = :owner_user_id'
        );
        $stmt->execute([
            'id' => $projectId,
            'owner_user_id' => $userId,
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Build a SELECT over the shared projects table joined to the project_roost
     * profile catalog. When $requireProfile is true an INNER JOIN is used so only
     * project_roost-known projects are returned (single source of truth); legacy
     * projects rows without a profile are excluded.
     */
    private function selectProjectsSql(string $where = '', string $orderBy = '', bool $requireProfile = false): string
    {
        $profileSelect = 'NULL AS display_name, NULL AS profile_slug, NULL AS profile_category, '
            . 'NULL AS profile_summary, NULL AS profile_preview_url, NULL AS profile_production_url, 0 AS archived';
        $join = '';

        if ($this->hasProjectRoostProfilesTable()) {
            $archivedExpr = $this->archivedExpr();
            $profileSelect = 'pr.display_name AS display_name, pr.slug AS profile_slug, pr.category AS profile_category, '
                . 'pr.summary AS profile_summary, pr.preview_url AS profile_preview_url, '
                . "pr.production_url AS profile_production_url, {$archivedExpr} AS archived";
            $joinType = $requireProfile ? 'INNER JOIN' : 'LEFT JOIN';
            $join = " {$joinType} project_roost_profiles pr ON pr.project_id = p.id";
        }

        return trim("SELECT p.*, {$profileSelect} FROM projects p{$join} {$where} {$orderBy}");
    }

    /**
     * SQL expression for the archived flag that degrades gracefully when the
     * project_roost_profiles.archived column has not been migrated in yet.
     */
    private function archivedExpr(): string
    {
        return $this->hasProjectRoostArchivedColumn() ? 'COALESCE(pr.archived, 0)' : '0';
    }

    private function hasProjectRoostArchivedColumn(): bool
    {
        if ($this->projectRoostArchivedAvailable !== null) {
            return $this->projectRoostArchivedAvailable;
        }

        if (!$this->hasProjectRoostProfilesTable()) {
            return $this->projectRoostArchivedAvailable = false;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM project_roost_profiles LIKE 'archived'");
            $this->projectRoostArchivedAvailable = $stmt !== false && $stmt->fetchColumn() !== false;
        } catch (\Throwable) {
            $this->projectRoostArchivedAvailable = false;
        }

        return $this->projectRoostArchivedAvailable;
    }

    private function hasProjectRoostProfilesTable(): bool
    {
        if ($this->projectRoostProfilesAvailable !== null) {
            return $this->projectRoostProfilesAvailable;
        }

        try {
            $stmt = $this->db->query("SHOW TABLES LIKE 'project_roost_profiles'");
            $this->projectRoostProfilesAvailable = $stmt !== false && $stmt->fetchColumn() !== false;
        } catch (\Throwable) {
            $this->projectRoostProfilesAvailable = false;
        }

        return $this->projectRoostProfilesAvailable;
    }
}
