<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdventurerRepository;
use App\Repositories\BadgeRepository;
use App\Repositories\XpLedgerRepository;

class GamificationService
{
    private AdventurerRepository $adventurerRepo;
    private BadgeRepository $badgeRepo;
    private XpLedgerRepository $xpLedgerRepo;

    public function __construct(
        AdventurerRepository $adventurerRepo,
        BadgeRepository $badgeRepo,
        XpLedgerRepository $xpLedgerRepo
    ) {
        $this->adventurerRepo = $adventurerRepo;
        $this->badgeRepo = $badgeRepo;
        $this->xpLedgerRepo = $xpLedgerRepo;
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
        $this->adventurerRepo->updateXpAndLevel($adventurerId, $newXp, $newLevel);

        // Log Ledger
        $this->xpLedgerRepo->addXp($adventurerId, $amount, $sourceType, $sourceRef);

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
        if ($level >= 5 && !$this->badgeRepo->hasBadge($adventurerId, 'level-5')) {
            $this->badgeRepo->awardBadge($adventurerId, 'level-5', 'High Five');
            $earned[] = 'High Five';
        }
        
        // Example: 1000 XP Badge
        if ($xp >= 1000 && !$this->badgeRepo->hasBadge($adventurerId, 'xp-1k')) {
            $this->badgeRepo->awardBadge($adventurerId, 'xp-1k', 'Kilo-XP');
            $earned[] = 'Kilo-XP';
        }

        return $earned;
    }
}
