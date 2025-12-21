<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProjectRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM projects ORDER BY group_name, title');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $project = $stmt->fetch();
        
        return $project ?: null;
    }

    public function getHomepageProjects(): array
    {
        $stmt = $this->db->query('SELECT * FROM projects WHERE show_on_homepage = 1 ORDER BY group_name, title');
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO projects (title, path, description, stage, status, version, group_name, repository_type, repository_url, show_on_homepage) 
             VALUES (:title, :path, :description, :stage, :status, :version, :group_name, :repository_type, :repository_url, :show_on_homepage)'
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
            'show_on_homepage' => (int)($data['show_on_homepage'] ?? true)
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = [
            'title', 'path', 'description', 'stage', 'status', 'version', 
            'group_name', 'repository_type', 'repository_url', 'show_on_homepage',
            'last_updated', 'last_build', 'last_commit_message', 'branch', 
            'git_commit', 'environments', 'project_type'
        ];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                if ($key === 'environments' && is_array($value)) {
                    $params[$key] = json_encode($value);
                } else {
                    $params[$key] = $value;
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE projects SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM projects WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function findByTitle(string $title): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE title = :title LIMIT 1');
        $stmt->execute(['title' => $title]);
        $project = $stmt->fetch();
        
        return $project ?: null;
    }

    public function findByPathLike(string $projectName): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE path LIKE :path LIMIT 1');
        $stmt->execute(['path' => "%/$projectName"]);
        $project = $stmt->fetch();
        
        return $project ?: null;
    }

    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM projects');
        return (int)$stmt->fetchColumn();
    }
}
