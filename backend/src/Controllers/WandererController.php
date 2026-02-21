<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\AdventurerRepository;

class WandererController
{
    private AdventurerRepository $adventurerRepo;

    public function __construct(AdventurerRepository $adventurerRepo)
    {
        $this->adventurerRepo = $adventurerRepo;
    }

    /**
     * GET /api/adventurers/{username}/wanderer
     * Get cross-project contribution stats for an adventurer.
     */
    public function stats(Request $request, Response $response): void
    {
        $username = $request->params['username'] ?? '';
        $adventurer = $this->adventurerRepo->findByGitHubUsername($username);

        if (!$adventurer) {
            $response->notFound('Adventurer not found.');
            return;
        }

        try {
            // Count distinct projects contributed to
            $stats = $this->adventurerRepo->getWandererStats($adventurer->id);

            // Get mastery details
            $mastery = $this->adventurerRepo->getMastery($adventurer->id);

            $wandererLevel = $this->calculateWandererLevel((int)($stats['project_count'] ?? 0));

            $response->success([
                'username' => $username,
                'wanderer_level' => $wandererLevel,
                'projects_touched' => (int)($stats['project_count'] ?? 0),
                'total_contributions' => (int)($stats['total_contributions'] ?? 0),
                'total_reviews' => (int)($stats['total_reviews'] ?? 0),
                'mastery' => $mastery,
                'wanderer_title' => $this->getWandererTitle($wandererLevel),
            ]);
        } catch (\Exception $e) {
            $response->success([
                'username' => $username,
                'wanderer_level' => 1,
                'projects_touched' => 0,
                'total_contributions' => 0,
                'total_reviews' => 0,
                'mastery' => [],
                'wanderer_title' => 'Local Explorer',
            ]);
        }
    }

    private function calculateWandererLevel(int $projectCount): int
    {
        if ($projectCount >= 10) return 5;
        if ($projectCount >= 7) return 4;
        if ($projectCount >= 5) return 3;
        if ($projectCount >= 3) return 2;
        return 1;
    }

    private function getWandererTitle(int $level): string
    {
        return match ($level) {
            5 => 'Realm Walker',
            4 => 'Globe Trotter',
            3 => 'Path Finder',
            2 => 'Trail Blazer',
            default => 'Local Explorer',
        };
    }
}
