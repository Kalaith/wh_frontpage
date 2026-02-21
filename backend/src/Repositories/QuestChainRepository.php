<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class QuestChainRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAllActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM quest_chains WHERE is_active = TRUE ORDER BY created_at ASC");
        return $stmt->fetchAll() ?: [];
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM quest_chains WHERE slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deleteAll(): void
    {
        $this->db->exec("DELETE FROM quest_chains");
    }

    public function import(array $pipeline): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO quest_chains (slug, name, description, steps, total_steps, reward_xp, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([
            $pipeline['slug'],
            $pipeline['name'],
            $pipeline['description'],
            json_encode($pipeline['steps']),
            count($pipeline['steps']),
            $pipeline['reward_xp']
        ]);
    }

    public function createChain(string $slug, string $name, string $description, array $steps, int $rewardXp): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO quest_chains (slug, name, description, steps, total_steps, reward_xp, is_active, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([
            $slug,
            $name,
            $description,
            json_encode($steps, JSON_UNESCAPED_SLASHES),
            count($steps),
            $rewardXp
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateSteps(int $chainId, array $steps): void
    {
        $stmt = $this->db->prepare(
            "UPDATE quest_chains SET steps = ?, total_steps = ? WHERE id = ?"
        );
        $stmt->execute([
            json_encode($steps, JSON_UNESCAPED_SLASHES),
            count($steps),
            $chainId
        ]);
    }
}
