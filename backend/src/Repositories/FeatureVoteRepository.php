<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class FeatureVoteRepository
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function findByRequestAndUser(int $requestId, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM feature_votes WHERE feature_id = :feature_id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['feature_id' => $requestId, 'user_id' => $userId]);
        $vote = $stmt->fetch();
        
        return $vote ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO feature_votes (feature_id, user_id, eggs_allocated) 
             VALUES (:feature_id, :user_id, :eggs_allocated)'
        );
        $stmt->execute([
            'feature_id' => $data['feature_id'],
            'user_id' => $data['user_id'],
            'eggs_allocated' => $data['eggs_allocated'] ?? 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function delete(int $requestId, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM feature_votes WHERE feature_id = :feature_id AND user_id = :user_id');
        return $stmt->execute(['feature_id' => $requestId, 'user_id' => $userId]);
    }

    public function getFeatureVoteTotals(int $featureId): int
    {
        $stmt = $this->db->prepare('SELECT SUM(eggs_allocated) FROM feature_votes WHERE feature_id = :feature_id');
        $stmt->execute(['feature_id' => $featureId]);
        return (int)$stmt->fetchColumn();
    }

    public function countAllVotes(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) FROM feature_votes');
        return (int)$stmt->fetchColumn();
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM feature_votes WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }
}
