<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\BossRepository;

class BossService
{
    private BossRepository $bossRepo;

    public function __construct(BossRepository $bossRepo)
    {
        $this->bossRepo = $bossRepo;
    }

    /**
     * Called when a quest is completed to deal damage to the active boss.
     * Damage scales linearly with XP (e.g., 1 XP = 10 Damage).
     */
    public function handleQuestCompletion(?int $projectId, int $xpEarned): void
    {
        if (!$projectId || $xpEarned <= 0) {
            return;
        }

        // Only damage the boss if it belongs to the same project as the quest
        $boss = $this->bossRepo->getByProjectId($projectId);

        if ($boss && $boss->status === 'active') {
            $damage = $xpEarned * 10; // 1 XP = 10 DMG (e.g., 50 XP quest = 500 DMG)
            $boss->takeDamage($damage);
            
            $this->bossRepo->save($boss);
        }
    }
}
