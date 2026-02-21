<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Boss;
use PDO;

class BossRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getActiveBoss(): ?Boss
    {
        try {
            $stmt = $this->db->query("SELECT * FROM bosses WHERE status = 'active' ORDER BY created_at DESC LIMIT 1");
            $row = $stmt->fetch();
            return $row ? new Boss($row) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAllActive(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM bosses WHERE status = 'active' ORDER BY created_at DESC");
            $results = [];
            while ($row = $stmt->fetch()) {
                $results[] = (new Boss($row))->toArray();
            }
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    public function getAll(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM bosses ORDER BY created_at DESC");
            $results = [];
            while ($row = $stmt->fetch()) {
                $results[] = (new Boss($row))->toArray();
            }
            return $results;
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getByProjectId(int $projectId): ?Boss
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM bosses WHERE project_id = ? AND status != 'defeated' ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$projectId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new Boss($row) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function save(Boss $boss): bool
    {
        try {
            if ($boss->id > 0) {
                $stmt = $this->db->prepare("
                    UPDATE bosses 
                    SET hp_current = ?, hp_total = ?, phase = ?, max_phase = ?, status = ?, defeated_at = ?
                    WHERE id = ?
                ");
                return $stmt->execute([
                    $boss->hp_current,
                    $boss->hp_total,
                    $boss->phase,
                    $boss->max_phase,
                    $boss->status,
                    $boss->defeated_at,
                    $boss->id
                ]);
            }
            return false;
        } catch (\Exception $e) {
            error_log("Failed to save Boss: " . $e->getMessage());
            return false;
        }
    }
}
