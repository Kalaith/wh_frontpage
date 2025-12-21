<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class EmailNotificationRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO email_notifications (user_id, type, subject, message, metadata, status, created_at, updated_at) 
             VALUES (:user_id, :type, :subject, :message, :metadata, :status, NOW(), NOW())'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'metadata' => json_encode($data['metadata'] ?? []),
            'status' => $data['status'] ?? 'pending'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findPending(int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM email_notifications WHERE status = "pending" ORDER BY created_at ASC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status, ?string $errorMessage = null): bool
    {
        $sql = 'UPDATE email_notifications SET status = :status, updated_at = NOW()';
        $params = ['id' => $id, 'status' => $status];
        
        if ($status === 'sent') {
            $sql .= ', sent_at = NOW()';
        }
        
        if ($errorMessage !== null) {
            $sql .= ', error_message = :error_message';
            $params['error_message'] = $errorMessage;
        }
        
        $sql .= ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
