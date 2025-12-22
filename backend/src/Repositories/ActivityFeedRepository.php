<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ActivityFeedRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function all(int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT af.*, u.username as user_name FROM activity_feed af LEFT JOIN users u ON af.user_id = u.id ORDER BY af.created_at DESC LIMIT :limit');
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO activity_feed (user_id, activity_type, message, reference_id, reference_type, metadata) 
             VALUES (:user_id, :activity_type, :message, :reference_id, :reference_type, :metadata)'
        );
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'activity_type' => $data['activity_type'],
            'message' => $data['message'],
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null
        ]);

        return (int)$this->db->lastInsertId();
    }
}
