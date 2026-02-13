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
}
