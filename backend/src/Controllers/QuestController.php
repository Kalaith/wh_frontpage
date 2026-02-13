<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\GitHubService;

class QuestController
{
    private GitHubService $github;

    public function __construct(GitHubService $github)
    {
        $this->github = $github;
    }

    public function index(Request $request, Response $response): void
    {
        // For starter quests, we can hardcode the repo or allow query params
        $owner = $request->getParam('owner', 'Kalaith');
        $repo = $request->getParam('repo', 'wh_frontpage');
        $class = $request->getParam('class');
        $difficulty = $request->getParam('difficulty');

        $labels = ['quest'];
        if ($class) {
            $labels[] = "class:{$class}";
        }
        if ($difficulty) {
            $labels[] = "difficulty:{$difficulty}";
        }

        try {
            $issues = $this->github->getIssues($owner, $repo, $labels);
            $quests = array_map([$this->github, 'parseQuest'], $issues);
            
            $response->success($quests);
        } catch (\Exception $e) {
            $response->error('Failed to fetch quests: ' . $e->getMessage());
        }
    }

    public function show(Request $request, Response $response, array $args): void
    {
        // TODO: Implement single quest view
        $response->error('Not implemented yet', 501);
    }
}
