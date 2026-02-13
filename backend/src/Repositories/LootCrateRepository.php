<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\LootCrate;
use PDO;

class LootCrateRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getByAdventurer(int $adventurerId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM loot_crates WHERE adventurer_id = ? ORDER BY created_at DESC");
            $stmt->execute([$adventurerId]);
            $results = [];
            while ($row = $stmt->fetch()) {
                $results[] = (new LootCrate($row))->toArray();
            }
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getUnopenedCount(int $adventurerId): int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM loot_crates WHERE adventurer_id = ? AND status = 'unopened'");
            $stmt->execute([$adventurerId]);
            return (int)$stmt->fetchColumn();
        } catch (\Exception $e) {
            return 0;
        }
    }

    public function findById(int $id): ?LootCrate
    {
        $stmt = $this->db->prepare("SELECT * FROM loot_crates WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? new LootCrate($row) : null;
    }

    public function create(int $adventurerId, string $rarity, string $source): int
    {
        $stmt = $this->db->prepare("INSERT INTO loot_crates (adventurer_id, rarity, source, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$adventurerId, $rarity, $source]);
        return (int)$this->db->lastInsertId();
    }

    public function openCrate(int $crateId, array $contents): bool
    {
        $stmt = $this->db->prepare("UPDATE loot_crates SET status = 'opened', contents = ?, opened_at = NOW() WHERE id = ? AND status = 'unopened'");
        return $stmt->execute([json_encode($contents), $crateId]);
    }
}
