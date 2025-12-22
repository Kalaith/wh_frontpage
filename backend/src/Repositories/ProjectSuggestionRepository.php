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
            'INSERT INTO project_suggestions (title, description, tags, status, user_id) 
             VALUES (:title, :description, :tags, :status, :user_id)'
        );
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'tags' => $data['tags'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'user_id' => $data['user_id']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getStats(): array
    {
        $stmt = $this->db->query('SELECT status, COUNT(*) as count FROM project_suggestions GROUP BY status');
        $rows = $stmt->fetchAll();
        
        $stats = [
            'total' => 0,
            'suggested' => 0,
            'under_review' => 0,
            'approved' => 0,
            'rejected' => 0
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
}
