<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\PerkToken;
use PDO;

class PerkTokenRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getByAdventurer(int $adventurerId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM perk_tokens WHERE adventurer_id = ? ORDER BY is_equipped DESC, created_at DESC");
            $stmt->execute([$adventurerId]);
            $results = [];
            while ($row = $stmt->fetch()) {
                $results[] = (new PerkToken($row))->toArray();
            }
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getEquipped(int $adventurerId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM perk_tokens WHERE adventurer_id = ? AND is_equipped = TRUE AND (expires_at IS NULL OR expires_at > NOW())");
            $stmt->execute([$adventurerId]);
            $results = [];
            while ($row = $stmt->fetch()) {
                $results[] = (new PerkToken($row))->toArray();
            }
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function award(int $adventurerId, string $slug, string $name, ?string $effect = null, ?string $expiresAt = null): int
    {
        $stmt = $this->db->prepare("INSERT INTO perk_tokens (adventurer_id, perk_slug, perk_name, perk_effect, expires_at, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$adventurerId, $slug, $name, $effect, $expiresAt]);
        return (int)$this->db->lastInsertId();
    }

    public function equip(int $perkId, int $adventurerId): bool
    {
        // Unequip current perks of this slug first (max 1 equipped per type)
        $stmt = $this->db->prepare("UPDATE perk_tokens SET is_equipped = FALSE WHERE adventurer_id = ? AND is_equipped = TRUE");
        $stmt->execute([$adventurerId]);

        $stmt = $this->db->prepare("UPDATE perk_tokens SET is_equipped = TRUE WHERE id = ? AND adventurer_id = ?");
        return $stmt->execute([$perkId, $adventurerId]);
    }

    public function unequip(int $perkId, int $adventurerId): bool
    {
        $stmt = $this->db->prepare("UPDATE perk_tokens SET is_equipped = FALSE WHERE id = ? AND adventurer_id = ?");
        return $stmt->execute([$perkId, $adventurerId]);
    }
}
