<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class QuestAcceptanceRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function create(int $adventurerId, string $questRef): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO quest_acceptances (adventurer_id, quest_ref, status, accepted_at)
             VALUES (?, ?, 'accepted', NOW()) 
             ON DUPLICATE KEY UPDATE status = 'accepted', completed_at = NULL"
        );
        $stmt->execute([$adventurerId, $questRef]);
    }

    public function updateStatus(int $adventurerId, string $questRef, string $status): void
    {
        $timestampCol = match ($status) {
            'submitted' => 'submitted_at = NOW()',
            'completed' => 'completed_at = NOW()',
            default => 'accepted_at = NOW()'
        };

        $stmt = $this->db->prepare(
            "UPDATE quest_acceptances 
             SET status = ?, $timestampCol 
             WHERE adventurer_id = ? AND quest_ref = ?"
        );
        $stmt->execute([$status, $adventurerId, $questRef]);
    }

    public function getStatus(int $adventurerId, string $questRef): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT status FROM quest_acceptances 
             WHERE adventurer_id = ? AND quest_ref = ?
             LIMIT 1"
        );
        $stmt->execute([$adventurerId, $questRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $row['status'] : null;
    }

    public function countCompletedByAdventurer(int $adventurerId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM quest_acceptances WHERE adventurer_id = ? AND status = 'completed'");
        $stmt->execute([$adventurerId]);
        return (int)$stmt->fetchColumn();
    }

    public function findByRef(int $adventurerId, string $questRef): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, status, review_notes FROM quest_acceptances WHERE adventurer_id = ? AND quest_ref = ?"
        );
        $stmt->execute([$adventurerId, $questRef]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAllByAdventurer(int $adventurerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, quest_ref, status, accepted_at, submitted_at, completed_at
             FROM quest_acceptances
             WHERE adventurer_id = ?
             ORDER BY accepted_at DESC"
        );
        $stmt->execute([$adventurerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateReviewStatus(int $acceptanceId, string $status, ?string $notes = null, ?int $reviewerId = null): void
    {
        if ($status === 'submitted') {
            $stmt = $this->db->prepare(
                "UPDATE quest_acceptances
                 SET status = 'submitted', submitted_at = NOW(), review_notes = ?
                 WHERE id = ?"
            );
            $stmt->execute([$notes, $acceptanceId]);
        } elseif ($status === 'completed') {
            $stmt = $this->db->prepare(
                "UPDATE quest_acceptances
                 SET status = 'completed', completed_at = NOW(), reviewer_adventurer_id = ?, review_notes = ?
                 WHERE id = ?"
            );
            $stmt->execute([$reviewerId, $notes, $acceptanceId]);
        } elseif ($status === 'rejected') {
            $stmt = $this->db->prepare(
                "UPDATE quest_acceptances
                 SET status = 'rejected', completed_at = NULL, reviewer_adventurer_id = ?, review_notes = ?
                 WHERE id = ?"
            );
            $stmt->execute([$reviewerId, $notes, $acceptanceId]);
        }
    }

    public function delete(int $acceptanceId): void
    {
        $stmt = $this->db->prepare("DELETE FROM quest_acceptances WHERE id = ?");
        $stmt->execute([$acceptanceId]);
    }
}
