<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class XpLedgerRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function addXp(int $adventurerId, int $amount, string $sourceType, ?string $sourceRef = null): void
    {
        $stmt = $this->db->prepare("INSERT INTO xp_ledger (adventurer_id, amount, source_type, source_ref, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$adventurerId, $amount, $sourceType, $sourceRef]);
    }
}
