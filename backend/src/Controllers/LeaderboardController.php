<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\AdventurerRepository;

class LeaderboardController
{
    private AdventurerRepository $adventurerRepo;

    public function __construct(AdventurerRepository $adventurerRepo)
    {
        $this->adventurerRepo = $adventurerRepo;
    }

    public function index(Request $request, Response $response): void
    {
        try {
            // Future: accept 'season_id' query param
            $leaderboard = $this->adventurerRepo->getLeaderboard();
            $response->success($leaderboard);
        } catch (\Exception $e) {
            $response->error('Failed to fetch leaderboard: ' . $e->getMessage(), 500);
        }
    }
}
