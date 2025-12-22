<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class EggTransactionRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO egg_transactions (user_id, amount, type, description, reference_id, reference_type) 
             VALUES (:user_id, :amount, :type, :description, :reference_id, :reference_type)'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'reference_type' => $data['reference_type'] ?? null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function getTotalSpent(): int
    {
        $stmt = $this->db->query('SELECT SUM(ABS(amount)) FROM egg_transactions WHERE amount < 0');
        return (int)$stmt->fetchColumn();
    }

    public function getTotalEarned(): int
    {
        $stmt = $this->db->query('SELECT SUM(amount) FROM egg_transactions WHERE amount > 0');
        return (int)$stmt->fetchColumn();
    }

    public function getBalanceForUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT SUM(amount) as balance FROM egg_transactions WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        
        return (int)($row['balance'] ?? 0);
    }

    public function getHistoryForUser(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare('SELECT * FROM egg_transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getSpentForUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT SUM(amount) FROM egg_transactions WHERE user_id = :user_id AND amount < 0');
        $stmt->execute(['user_id' => $userId]);
        return abs((int)$stmt->fetchColumn());
    }

    public function getEarnedForUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT SUM(amount) FROM egg_transactions WHERE user_id = :user_id AND amount > 0');
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
