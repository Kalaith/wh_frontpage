<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class BadgeRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function hasBadge(int $adventurerId, string $badgeSlug): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM adventurer_badges WHERE adventurer_id = ? AND badge_slug = ?");
        $stmt->execute([$adventurerId, $badgeSlug]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function awardBadge(int $adventurerId, string $badgeSlug, string $badgeName): void
    {
        $stmt = $this->db->prepare("INSERT INTO adventurer_badges (adventurer_id, badge_slug, badge_name, earned_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$adventurerId, $badgeSlug, $badgeName]);
    }
}
