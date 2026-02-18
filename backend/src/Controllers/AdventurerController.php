<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\AdventurerRepository;

class AdventurerController
{
    private AdventurerRepository $repo;

    public function __construct(AdventurerRepository $repo)
    {
        $this->repo = $repo;
    }

    public function show(Request $request, Response $response, array $args = []): void
    {
        $username = $args['username'] ?? $request->getParam('username');
        if (!$username) {
            $response->error('Username required');
            return;
        }

        $adventurer = $this->repo->findByGitHubUsername($username);
        
        if (!$adventurer) {
            $response->error('Adventurer not found', 404);
            return;
        }

        $badges = $this->repo->getBadges($adventurer->id);
        $mastery = $this->repo->getMastery($adventurer->id);

        $data = $adventurer->toArray();
        $data['badges'] = $badges;
        $data['mastery'] = $mastery;

        $response->success($data);
    }
}
