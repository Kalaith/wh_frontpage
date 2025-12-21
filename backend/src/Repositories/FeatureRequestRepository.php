<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class FeatureRequestRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function all(): array
    {
        $sql = 'SELECT fr.*, p.title as project_title, u.username as creator_username 
                FROM feature_requests fr
                LEFT JOIN projects p ON fr.project_id = p.id
                LEFT JOIN users u ON fr.user_id = u.id
                ORDER BY fr.created_at DESC';
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM feature_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $request = $stmt->fetch();
        
        return $request ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO feature_requests (title, description, status, priority, type, project_id, user_id) 
             VALUES (:title, :description, :status, :priority, :type, :project_id, :user_id)'
        );
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'Open',
            'priority' => $data['priority'] ?? 'Medium',
            'type' => $data['type'] ?? 'Feature',
            'project_id' => $data['project_id'] ?? null,
            'user_id' => $data['user_id']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = ['title', 'description', 'status', 'priority', 'type', 'project_id'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fields[] = "$key = :$key";
                $params[$key] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE feature_requests SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    public function countAll(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM feature_requests');
        return (int)$stmt->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM feature_requests WHERE status = :status');
        $stmt->execute(['status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function getStats(): array
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) as count FROM feature_requests GROUP BY status');
        $rows = $stmt->fetchAll();
        
        $stats = [
            'total' => 0,
            'open' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'closed' => 0
        ];

        foreach ($rows as $row) {
            $status = strtolower(str_replace(' ', '_', $row['status']));
            if (array_key_exists($status, $stats)) {
                $stats[$status] = (int)$row['count'];
            }
            $stats['total'] += (int)$row['count'];
        }

        return $stats;
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM feature_requests WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function countByUserAndStatus(int $userId, string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM feature_requests WHERE user_id = :user_id AND status = :status');
        $stmt->execute(['user_id' => $userId, 'status' => $status]);
        return (int)$stmt->fetchColumn();
    }

    public function getByFilters(array $filters = [], string $sortBy = 'created_at', string $sortDirection = 'desc', ?int $limit = null): array
    {
        $where = [];
        $params = [];

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $where[] = 'fr.status = :status';
            $params['status'] = $filters['status'];
        }

        if (isset($filters['project_id']) && $filters['project_id']) {
            $where[] = 'fr.project_id = :project_id';
            $params['project_id'] = $filters['project_id'];
        }

        if (isset($filters['category']) && $filters['category']) {
            $where[] = 'fr.category = :category';
            $params['category'] = $filters['category'];
        }

        if (isset($filters['search']) && $filters['search']) {
            $where[] = '(fr.title LIKE :search OR fr.description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $sql = 'SELECT fr.*, p.title as project_title, u.username as creator_username, u.display_name as creator_display_name 
                FROM feature_requests fr
                LEFT JOIN projects p ON fr.project_id = p.id
                LEFT JOIN users u ON fr.user_id = u.id';
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $allowedSort = ['created_at', 'total_eggs', 'vote_count', 'status', 'priority_level'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';

        $sql .= " ORDER BY fr.$sortBy $sortDirection";

        if ($limit) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(function($row) {
            if (isset($row['tags']) && is_string($row['tags'])) {
                $row['tags'] = json_decode($row['tags'], true) ?: [];
            }
            return $row;
        }, $rows);
    }
}
