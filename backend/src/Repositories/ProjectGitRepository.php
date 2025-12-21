<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProjectGitRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function findByProjectId(int $projectId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM projects_git WHERE project_id = :project_id LIMIT 1');
        $stmt->execute(['project_id' => $projectId]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    public function findByProjectIds(array $projectIds): array
    {
        if (empty($projectIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
        $stmt = $this->db->prepare("SELECT * FROM projects_git WHERE project_id IN ($placeholders)");
        $stmt->execute($projectIds);
        
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $results[$row['project_id']] = $row;
        }
        
        return $results;
    }

    public function upsert(int $projectId, array $data): void
    {
        $existing = $this->findByProjectId($projectId);
        
        if ($existing) {
            $this->update($projectId, $data);
        } else {
            $this->create($projectId, $data);
        }
    }

    private function create(int $projectId, array $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO projects_git (project_id, last_updated, last_build, last_commit_message, branch, git_commit, environments)
             VALUES (:project_id, :last_updated, :last_build, :last_commit_message, :branch, :git_commit, :environments)'
        );
        
        $stmt->execute([
            'project_id' => $projectId,
            'last_updated' => $data['last_updated'] ?? null,
            'last_build' => $data['last_build'] ?? null,
            'last_commit_message' => $data['last_commit_message'] ?? null,
            'branch' => $data['branch'] ?? null,
            'git_commit' => $data['git_commit'] ?? null,
            'environments' => isset($data['environments']) ? json_encode($data['environments']) : null,
        ]);
    }

    private function update(int $projectId, array $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE projects_git SET 
                last_updated = :last_updated,
                last_build = :last_build,
                last_commit_message = :last_commit_message,
                branch = :branch,
                git_commit = :git_commit,
                environments = :environments,
                updated_at = NOW()
             WHERE project_id = :project_id'
        );
        
        $stmt->execute([
            'project_id' => $projectId,
            'last_updated' => $data['last_updated'] ?? null,
            'last_build' => $data['last_build'] ?? null,
            'last_commit_message' => $data['last_commit_message'] ?? null,
            'branch' => $data['branch'] ?? null,
            'git_commit' => $data['git_commit'] ?? null,
            'environments' => isset($data['environments']) ? json_encode($data['environments']) : null,
        ]);
    }

    public function delete(int $projectId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM projects_git WHERE project_id = :project_id');
        return $stmt->execute(['project_id' => $projectId]);
    }
}
