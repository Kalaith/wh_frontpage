<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdventurerRepository;
use App\Repositories\QuestAcceptanceRepository;

class RankService
{
    private AdventurerRepository $adventurerRepo;
    private QuestAcceptanceRepository $questAcceptanceRepo;

    /** Rank thresholds: [rank => [min_completed_quests, min_xp]] */
    private const RANK_THRESHOLDS = [
        'Iron'    => [0,  0],
        'Silver'  => [3,  150],
        'Gold'    => [10, 500],
        'Jade'    => [25, 1500],
        'Diamond' => [50, 5000],
    ];

    private const RANK_ORDER = ['Iron', 'Silver', 'Gold', 'Jade', 'Diamond'];

    public function __construct(AdventurerRepository $adventurerRepo, QuestAcceptanceRepository $questAcceptanceRepo)
    {
        $this->adventurerRepo = $adventurerRepo;
        $this->questAcceptanceRepo = $questAcceptanceRepo;
    }

    /**
     * Check if an adventurer meets the rank requirement for a quest.
     */
    public function meetsRankRequirement(int $adventurerId, string $requiredRank): bool
    {
        $adventurerRank = $this->getAdventurerRank($adventurerId);
        return $this->rankIndex($adventurerRank) >= $this->rankIndex($requiredRank);
    }

    /**
     * Get the current rank of an adventurer from the DB.
     */
    public function getAdventurerRank(int $adventurerId): string
    {
        $rank = $this->adventurerRepo->getRank($adventurerId);
        return $rank ?: 'Iron';
    }

    /**
     * Recalculate and update rank after quest completion.
     * Returns [old_rank, new_rank, promoted].
     */
    public function recalculateRank(int $adventurerId): array
    {
        $oldRank = $this->getAdventurerRank($adventurerId);

        // Count completed quests
        $completedQuests = $this->questAcceptanceRepo->countCompletedByAdventurer($adventurerId);

        // Get total XP
        $totalXp = $this->adventurerRepo->getXp($adventurerId);

        // Determine highest rank met
        $newRank = 'Iron';
        foreach (self::RANK_THRESHOLDS as $rank => [$minQuests, $minXp]) {
            if ($completedQuests >= $minQuests && $totalXp >= $minXp) {
                $newRank = $rank;
            }
        }

        // Update if changed
        if (
            $newRank !== $oldRank &&
            $this->rankIndex($newRank) > $this->rankIndex($oldRank)
        ) {
            $this->adventurerRepo->updateRank($adventurerId, $newRank);
        }

        return [
            'old_rank' => $oldRank,
            'new_rank' => $newRank,
            'promoted' => $newRank !== $oldRank && $this->rankIndex($newRank) > $this->rankIndex($oldRank),
        ];
    }

    /**
     * Get rank progress: current rank, next rank, quests/xp needed.
     */
    public function getRankProgress(int $adventurerId): array
    {
        $currentRank = $this->getAdventurerRank($adventurerId);
        $currentIdx = $this->rankIndex($currentRank);

        // Count completed quests
        $completedQuests = $this->questAcceptanceRepo->countCompletedByAdventurer($adventurerId);

        // Get total XP
        $totalXp = $this->adventurerRepo->getXp($adventurerId);

        $nextRank = null;
        $questsNeeded = 0;
        $xpNeeded = 0;
        $progressPercent = 100;

        if ($currentIdx < count(self::RANK_ORDER) - 1) {
            $nextRank = self::RANK_ORDER[$currentIdx + 1];
            [$reqQuests, $reqXp] = self::RANK_THRESHOLDS[$nextRank];
            $questsNeeded = max(0, $reqQuests - $completedQuests);
            $xpNeeded = max(0, $reqXp - $totalXp);

            // Progress = average of quest progress and xp progress toward next rank
            $questProgress = $reqQuests > 0 ? min(100, ($completedQuests / $reqQuests) * 100) : 100;
            $xpProgress = $reqXp > 0 ? min(100, ($totalXp / $reqXp) * 100) : 100;
            $progressPercent = (int) round(($questProgress + $xpProgress) / 2);
        }

        return [
            'current_rank' => $currentRank,
            'next_rank' => $nextRank,
            'completed_quests' => $completedQuests,
            'total_xp' => $totalXp,
            'quests_needed' => $questsNeeded,
            'xp_needed' => $xpNeeded,
            'progress_percent' => $progressPercent,
        ];
    }

    private function rankIndex(string $rank): int
    {
        $idx = array_search($rank, self::RANK_ORDER, true);
        return $idx !== false ? (int) $idx : 0;
    }
}
