<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LootCrateRepository;
use App\Repositories\AdventurerRepository;
use PDO;

class LootCrateService
{
    private PDO $db;
    private LootCrateRepository $crateRepo;
    private AdventurerRepository $adventurerRepo;
    private GamificationService $gamificationService;

    private const RARITY_WEIGHTS = [
        'common'    => 50,
        'uncommon'  => 30,
        'rare'      => 13,
        'epic'      => 5,
        'legendary' => 2,
    ];

    private const RARITY_XP_RANGES = [
        'common'    => [10, 30],
        'uncommon'  => [25, 75],
        'rare'      => [50, 200],
        'epic'      => [150, 500],
        'legendary' => [400, 1000],
    ];

    private const RARITY_BADGE_POOL = [
        'rare'      => [['slug' => 'lucky-find', 'name' => 'Lucky Find']],
        'epic'      => [['slug' => 'treasure-hunter', 'name' => 'Treasure Hunter']],
        'legendary' => [['slug' => 'jackpot', 'name' => 'Jackpot!'], ['slug' => 'golden-egg', 'name' => 'Golden Egg']],
    ];

    private const TITLE_POOL = [
        'common'    => ['Novice Looter'],
        'uncommon'  => ['Chest Opener', 'Fortune Seeker'],
        'rare'      => ['Treasure Digger', 'Relic Finder'],
        'epic'      => ['Vault Raider', 'Loot Goblin'],
        'legendary' => ['Dragon Hoarder', 'Midas Touch', 'The Chosen One'],
    ];

    public function __construct(PDO $db, LootCrateRepository $crateRepo, AdventurerRepository $adventurerRepo, GamificationService $gamificationService)
    {
        $this->db = $db;
        $this->crateRepo = $crateRepo;
        $this->adventurerRepo = $adventurerRepo;
        $this->gamificationService = $gamificationService;
    }

    /**
     * Roll a random rarity based on weighted distribution.
     */
    public function rollRarity(): string
    {
        $total = array_sum(self::RARITY_WEIGHTS);
        $roll = random_int(1, $total);
        $cumulative = 0;

        foreach (self::RARITY_WEIGHTS as $rarity => $weight) {
            $cumulative += $weight;
            if ($roll <= $cumulative) {
                return $rarity;
            }
        }

        return 'common';
    }

    /**
     * Award a loot crate to an adventurer (e.g. after a PR merge).
     */
    public function awardCrate(int $adventurerId, string $source = 'quest'): array
    {
        $rarity = $this->rollRarity();
        $crateId = $this->crateRepo->create($adventurerId, $rarity, $source);

        return [
            'crate_id' => $crateId,
            'rarity' => $rarity,
        ];
    }

    /**
     * Open a crate and generate randomized contents.
     */
    public function openCrate(int $crateId, int $adventurerId): array
    {
        $crate = $this->crateRepo->findById($crateId);
        if (!$crate) {
            throw new \Exception('Crate not found');
        }
        if ($crate->adventurer_id !== $adventurerId) {
            throw new \Exception('This crate does not belong to you');
        }
        if ($crate->status === 'opened') {
            throw new \Exception('Crate already opened');
        }

        $contents = $this->generateContents($crate->rarity);

        // Apply rewards
        if ($contents['xp'] > 0) {
            $this->gamificationService->awardXp($adventurerId, $contents['xp'], 'crate', "Loot Crate #{$crateId}");
        }

        // Mark crate as opened
        $this->crateRepo->openCrate($crateId, $contents);

        return $contents;
    }

    /**
     * Generate random contents based on rarity.
     */
    private function generateContents(string $rarity): array
    {
        $xpRange = self::RARITY_XP_RANGES[$rarity] ?? [10, 30];
        $xp = random_int($xpRange[0], $xpRange[1]);

        $contents = [
            'xp' => $xp,
            'badge' => null,
            'title' => null,
            'perk' => null,
        ];

        // Chance for badge (rare+ only)
        $badges = self::RARITY_BADGE_POOL[$rarity] ?? [];
        if (!empty($badges) && random_int(1, 100) <= 40) {
            $contents['badge'] = $badges[array_rand($badges)];
        }

        // Chance for title
        $titles = self::TITLE_POOL[$rarity] ?? [];
        if (!empty($titles) && random_int(1, 100) <= 25) {
            $contents['title'] = $titles[array_rand($titles)];
        }

        return $contents;
    }
}
