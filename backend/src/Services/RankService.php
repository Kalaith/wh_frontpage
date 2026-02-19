<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

class RankService
{
    private PDO $db;
    private ?bool $hasRankColumn = null;

    /** Rank thresholds: [rank => [min_completed_quests, min_xp]] */
    private const RANK_THRESHOLDS = [
        'Iron'    => [0,  0],
        'Silver'  => [3,  150],
        'Gold'    => [10, 500],
        'Jade'    => [25, 1500],
        'Diamond' => [50, 5000],
    ];

    private const RANK_ORDER = ['Iron', 'Silver', 'Gold', 'Jade', 'Diamond'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
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
        if (!$this->rankColumnExists()) {
            return 'Iron';
        }

        $stmt = $this->db->prepare("SELECT `rank` FROM adventurers WHERE id = ?");
        $stmt->execute([$adventurerId]);
        $rank = $stmt->fetchColumn();
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
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM quest_acceptances WHERE adventurer_id = ? AND status = 'completed'"
        );
        $stmt->execute([$adventurerId]);
        $completedQuests = (int) $stmt->fetchColumn();

        // Get total XP
        $stmt = $this->db->prepare("SELECT xp_total FROM adventurers WHERE id = ?");
        $stmt->execute([$adventurerId]);
        $totalXp = (int) $stmt->fetchColumn();

        // Determine highest rank met
        $newRank = 'Iron';
        foreach (self::RANK_THRESHOLDS as $rank => [$minQuests, $minXp]) {
            if ($completedQuests >= $minQuests && $totalXp >= $minXp) {
                $newRank = $rank;
            }
        }

        // Update if changed
        if (
            $this->rankColumnExists() &&
            $newRank !== $oldRank &&
            $this->rankIndex($newRank) > $this->rankIndex($oldRank)
        ) {
            $stmt = $this->db->prepare("UPDATE adventurers SET `rank` = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newRank, $adventurerId]);
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
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM quest_acceptances WHERE adventurer_id = ? AND status = 'completed'"
        );
        $stmt->execute([$adventurerId]);
        $completedQuests = (int) $stmt->fetchColumn();

        // Get total XP
        $stmt = $this->db->prepare("SELECT xp_total FROM adventurers WHERE id = ?");
        $stmt->execute([$adventurerId]);
        $totalXp = (int) $stmt->fetchColumn();

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

    private function rankColumnExists(): bool
    {
        if ($this->hasRankColumn !== null) {
            return $this->hasRankColumn;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM adventurers LIKE 'rank'");
            $this->hasRankColumn = (bool)$stmt->fetch();
        } catch (\Throwable) {
            $this->hasRankColumn = false;
        }

        return $this->hasRankColumn;
    }
}
