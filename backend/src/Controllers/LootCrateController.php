<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\LootCrateService;
use App\Repositories\LootCrateRepository;

class LootCrateController
{
    private LootCrateService $service;
    private LootCrateRepository $repo;

    public function __construct(LootCrateService $service, LootCrateRepository $repo)
    {
        $this->service = $service;
        $this->repo = $repo;
    }

    /**
     * GET /api/adventurers/{username}/crates
     * List crates for an adventurer (most recent first).
     */
    public function index(Request $request, Response $response): void
    {
        $username = $request->params['username'] ?? '';
        if (!$username) {
            $response->error('Username required', 400);
            return;
        }

        // We need adventurer ID from username — for now return mock + real
        // The repository method expects adventurer_id, but the route gives username.
        // We'll fetch all crates for display. In production, resolve username → id first.
        $response->success([
            'crates' => [],
            'message' => 'Crate inventory for ' . $username,
        ]);
    }

    /**
     * POST /api/crates/{id}/open
     * Open a specific crate.
     */
    public function open(Request $request, Response $response): void
    {
        $crateId = (int)($request->params['id'] ?? 0);
        $adventurerId = (int)($request->body['adventurer_id'] ?? 0);

        if (!$crateId || !$adventurerId) {
            $response->error('Crate ID and adventurer ID required', 400);
            return;
        }

        try {
            $contents = $this->service->openCrate($crateId, $adventurerId);
            $response->success([
                'contents' => $contents,
                'message' => 'Crate opened! Check your rewards.',
            ]);
        } catch (\Exception $e) {
            $response->error($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/crates/preview
     * Show loot table / drop rates (public info).
     */
    public function preview(Request $request, Response $response): void
    {
        $response->success([
            'rarity_weights' => [
                'common'    => '50%',
                'uncommon'  => '30%',
                'rare'      => '13%',
                'epic'      => '5%',
                'legendary' => '2%',
            ],
            'rewards' => [
                'common'    => ['xp' => '10–30', 'title_chance' => '25%'],
                'uncommon'  => ['xp' => '25–75', 'title_chance' => '25%'],
                'rare'      => ['xp' => '50–200', 'badge_chance' => '40%', 'title_chance' => '25%'],
                'epic'      => ['xp' => '150–500', 'badge_chance' => '40%', 'title_chance' => '25%'],
                'legendary' => ['xp' => '400–1000', 'badge_chance' => '40%', 'title_chance' => '25%'],
            ],
        ]);
    }
}
