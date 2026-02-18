<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\BossRepository;
use App\Models\Boss;

class BossController
{
    private BossRepository $repo;

    public function __construct(BossRepository $repo)
    {
        $this->repo = $repo;
    }

    public function current(Request $request, Response $response): void
    {
        $boss = $this->repo->getActiveBoss();
        
        if (!$boss) {
            // Mock boss if none exists for demonstration
            $boss = new Boss([
                'id' => 1,
                'name' => 'The Monolith of Legacy Code',
                'description' => 'A towering structure of tangled logic that threatens to collapse the entire codebase. Its method calls are infinite, its dependencies circular.',
                'github_issue_url' => 'https://github.com/Kalaith/wh_frontpage/issues/1',
                'threat_level' => 4,
                'hp_total' => 5000,
                'hp_current' => 3250,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $bossData = $boss->toArray();
        $response->success($this->normalizeBoss($bossData));
    }

    private function normalizeBoss(array $boss): array
    {
        $metadata = [];
        $description = (string)($boss['description'] ?? '');

        if (preg_match('/\n\nMetadata:\s*(\{.*\})\s*$/s', $description, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
                $description = trim((string)preg_replace('/\n\nMetadata:\s*\{.*\}\s*$/s', '', $description));
            }
        }

        $boss['description'] = $description;
        $boss['labels'] = is_array($metadata['labels'] ?? null) ? $metadata['labels'] : [];
        $boss['threat_type'] = $metadata['threat_type'] ?? null;
        $boss['deadline'] = $metadata['deadline'] ?? null;
        $boss['risk_level'] = $metadata['risk_level'] ?? null;
        $boss['rollback_plan'] = $metadata['rollback_plan'] ?? null;
        $boss['kill_criteria'] = is_array($metadata['kill_criteria'] ?? null) ? $metadata['kill_criteria'] : [];
        $boss['hp_tasks'] = is_array($metadata['hp_tasks'] ?? null) ? $metadata['hp_tasks'] : [];
        $boss['proof_required'] = is_array($metadata['proof_required'] ?? null) ? $metadata['proof_required'] : [];

        return $boss;
    }
}
