<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Models\WeeklyHeist;

class WeeklyHeistRepository
{
    public function __construct(private readonly PDO $db) {}

    public function getActive(): ?WeeklyHeist
    {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM weekly_heists 
            WHERE is_active = 1 
              AND starts_at <= NOW() 
              AND ends_at > NOW() 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            return new WeeklyHeist($row);
        }

        return null;
    }

    public function findById(int $id): ?WeeklyHeist
    {
        $stmt = $this->db->prepare("SELECT * FROM weekly_heists WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if ($row) {
            return new WeeklyHeist($row);
        }
        return null;
    }
}
