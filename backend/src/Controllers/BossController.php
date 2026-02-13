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
        
        $response->success($boss->toArray());
    }
}
