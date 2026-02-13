<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdventurerRepository;
use PDO;

class GamificationService
{
    private PDO $db;
    private AdventurerRepository $adventurerRepo;

    public function __construct(PDO $db, AdventurerRepository $adventurerRepo)
    {
        $this->db = $db;
        $this->adventurerRepo = $adventurerRepo;
    }

    public function awardXp(int $adventurerId, int $amount, string $sourceType, string $sourceRef = ''): array
    {
        $adventurer = $this->adventurerRepo->findById($adventurerId);
        if (!$adventurer) {
            throw new \Exception("Adventurer not found");
        }

        $currentXp = $adventurer->xp_total;
        $newXp = $currentXp + $amount;
        $currentLevel = $adventurer->level;
        $newLevel = $currentLevel;

        // Level Up Logic: Threshold = Level * 100 * 1.5 (simplified)
        // Or simpler: Level = floor(sqrt(XP / 100))
        // Let's stick to the script logic:
        while (true) {
            $threshold = (int)($newLevel * 100 * 1.5) + ($newLevel > 1 ? ($newLevel - 1) * 100 * 1.5 : 0); 
            // Actually, let's use a simpler formula for robustness:
            // XP required for Level L = 100 * (L-1)^2
            // Level = floor(1 + sqrt(XP / 100))
            $calculatedLevel = floor(1 + sqrt($newXp / 100));
            
            if ($calculatedLevel > $newLevel) {
                $newLevel = (int)$calculatedLevel;
            } else {
                break;
            }
        }

        // Update Adventurer
        $stmt = $this->db->prepare("UPDATE adventurers SET xp_total = ?, level = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newXp, $newLevel, $adventurerId]);

        // Log Ledger
        $stmt = $this->db->prepare("INSERT INTO xp_ledger (adventurer_id, amount, source_type, source_ref, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$adventurerId, $amount, $sourceType, $sourceRef]);

        // Check for Badges (Basic Logic)
        $newBadges = $this->checkBadges($adventurerId, $newXp, $newLevel);

        return [
            'old_xp' => $currentXp,
            'new_xp' => $newXp,
            'old_level' => $currentLevel,
            'new_level' => $newLevel,
            'leveled_up' => $newLevel > $currentLevel,
            'badges_earned' => $newBadges
        ];
    }

    private function checkBadges(int $adventurerId, int $xp, int $level): array
    {
        $earned = [];
        // Example: Level 5 Badge
        if ($level >= 5 && !$this->hasBadge($adventurerId, 'level-5')) {
            $this->awardBadge($adventurerId, 'level-5', 'High Five', 'Reached Level 5');
            $earned[] = 'High Five';
        }
        
        // Example: 1000 XP Badge
        if ($xp >= 1000 && !$this->hasBadge($adventurerId, 'xp-1k')) {
            $this->awardBadge($adventurerId, 'xp-1k', 'Kilo-XP', 'Earned 1,000 XP');
            $earned[] = 'Kilo-XP';
        }

        return $earned;
    }

    private function hasBadge(int $adventurerId, string $slug): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM adventurer_badges WHERE adventurer_id = ? AND badge_slug = ?");
        $stmt->execute([$adventurerId, $slug]);
        return (bool)$stmt->fetchColumn();
    }

    private function awardBadge(int $adventurerId, string $slug, string $name, string $desc): void
    {
        $stmt = $this->db->prepare("INSERT INTO adventurer_badges (adventurer_id, badge_slug, badge_name, earned_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$adventurerId, $slug, $name]);
    }
}
