<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ProjectSuggestionRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function all(): array
    {
        $stmt = $this->db->query('SELECT ps.*, u.username FROM project_suggestions ps LEFT JOIN users u ON ps.user_id = u.id ORDER BY ps.created_at DESC');
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO project_suggestions (title, description, tags, status, user_id, rationale) 
             VALUES (:title, :description, :tags, :status, :user_id, :rationale)'
        );
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'tags' => $data['tags'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'user_id' => $data['user_id'],
            'rationale' => $data['rationale'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): ?\App\Models\ProjectSuggestion
    {
        $stmt = $this->db->prepare('SELECT ps.*, u.username FROM project_suggestions ps LEFT JOIN users u ON ps.user_id = u.id WHERE ps.id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return null;
        }
        
        // Map username to user_name (DTO expects user_name I think? let's check DTO)
        // ProjectSuggestion DTO (step 107) has submitted_by.
        // It doesn't seem to have user_name explicitly but submitted_by.
        // The query in all() fetches username.
        
        $data = $row;
        if (isset($row['username'])) {
            $data['submitted_by'] = $row['username'];
        }
        $data['suggested_group'] = $row['tags'] ?? 'other';
        
        return new \App\Models\ProjectSuggestion($data);
    }
    
    public function getByFilters(array $filters = [], string $sortBy = 'votes', string $sortDirection = 'desc', ?int $limit = null): array
    {
        $query = 'SELECT ps.*, u.username FROM project_suggestions ps LEFT JOIN users u ON ps.user_id = u.id';
        $conditions = [];
        $params = [];
        
        if (!empty($filters['group'])) {
            $conditions[] = 'ps.tags = :group'; // map group filter to tags column
            $params['group'] = $filters['group'];
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = 'ps.status = :status';
            $params['status'] = $filters['status'];
        }
        
        if (!empty($conditions)) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }
        
        // Whitelist sort columns
        $allowedSorts = ['votes', 'created_at', 'name'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'votes';
        }
        
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        $query .= " ORDER BY ps.{$sortBy} {$sortDirection}";
        
        if ($limit) {
            $query .= ' LIMIT ' . (int)$limit;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        $results = [];
        foreach ($stmt->fetchAll() as $row) {
            $data = $row;
            if (isset($row['username'])) {
                $data['submitted_by'] = $row['username'];
            }
            $data['suggested_group'] = $row['tags'] ?? 'other';
            $data['user_id'] = $row['user_id'] ?? null;
            $results[] = new \App\Models\ProjectSuggestion($data);
        }
        
        return $results;
    }
    
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['name', 'description', 'suggested_group', 'rationale', 'votes', 'status'])) {
                $fields[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE project_suggestions SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
