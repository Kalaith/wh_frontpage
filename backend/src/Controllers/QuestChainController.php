<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use PDO;

class QuestChainController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * GET /api/quest-chains
     * List all active quest chains with step details.
     */
    public function index(Request $request, Response $response): void
    {
        try {
            $stmt = $this->db->query("SELECT * FROM quest_chains WHERE is_active = TRUE ORDER BY created_at ASC");
            $chains = $stmt->fetchAll();

            $result = array_map(fn ($chain) => $this->normalizeChain($chain), $chains ?: []);

            $response->success($result);
        } catch (\Exception $e) {
            // Fallback mock data if tables don't exist yet
            $response->success($this->getMockChains());
        }
    }

    /**
     * GET /api/quest-chains/{slug}
     * Get a single quest chain by slug.
     */
    public function show(Request $request, Response $response): void
    {
        $slug = $request->params['slug'] ?? '';
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM quest_chains WHERE slug = ?");
            $stmt->execute([$slug]);
            $chain = $stmt->fetch();

            if (!$chain) {
                // Try mock data
                $mocks = $this->getMockChains();
                $chain = array_values(array_filter($mocks, fn($c) => $c['slug'] === $slug))[0] ?? null;
                if (!$chain) {
                    $response->notFound('Quest chain not found.');
                    return;
                }
            } else {
                $chain = $this->normalizeChain($chain);
            }

            $response->success($chain);
        } catch (\Exception $e) {
            $mocks = $this->getMockChains();
            $chain = array_values(array_filter($mocks, fn($c) => $c['slug'] === $slug))[0] ?? null;
            $chain ? $response->success($chain) : $response->notFound('Quest chain not found.');
        }
    }

    private function getMockChains(): array
    {
        return [
            [
                'id' => 1,
                'slug' => 'the-hatchlings-path',
                'name' => "The Hatchling's Path",
                'description' => 'Every adventurer begins somewhere. Complete these foundational quests to earn your wings.',
                'steps' => [
                    ['title' => 'First Steps', 'description' => 'Fork a Web Hatchery repository', 'xp' => 25],
                    ['title' => 'First Commit', 'description' => 'Push your first commit to a branch', 'xp' => 50],
                    ['title' => 'First Pull Request', 'description' => 'Open a Pull Request', 'xp' => 75],
                    ['title' => 'First Merge', 'description' => 'Get your PR merged into main', 'xp' => 100],
                    ['title' => 'Badge Collection', 'description' => 'Earn your first badge', 'xp' => 50],
                ],
                'total_steps' => 5,
                'reward_xp' => 500,
                'reward_badge_slug' => 'hatchling-graduate',
                'reward_title' => 'Graduated Hatchling',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'slug' => 'bug-hunter-saga',
                'name' => 'Bug Hunter Saga',
                'description' => 'Track down and squash the pests that plague our codebases.',
                'steps' => [
                    ['title' => 'Spot the Bug', 'description' => 'File a bug report with reproduction steps', 'xp' => 30],
                    ['title' => 'First Squash', 'description' => 'Fix and close a bug issue', 'xp' => 100],
                    ['title' => 'Triple Threat', 'description' => 'Fix 3 bugs in a single week', 'xp' => 150],
                    ['title' => 'Exterminator', 'description' => 'Fix 10 bugs total', 'xp' => 200],
                ],
                'total_steps' => 4,
                'reward_xp' => 750,
                'reward_badge_slug' => 'master-exterminator',
                'reward_title' => 'Master Exterminator',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'slug' => 'the-architects-journey',
                'name' => "The Architect's Journey",
                'description' => 'Design, build, and ship a significant feature from concept to deployment.',
                'steps' => [
                    ['title' => 'Blueprint', 'description' => 'Submit a feature proposal issue', 'xp' => 50],
                    ['title' => 'Foundation', 'description' => 'Create the backend implementation', 'xp' => 150],
                    ['title' => 'Facade', 'description' => 'Build the frontend UI', 'xp' => 150],
                    ['title' => 'Inspection', 'description' => 'Pass code review from a maintainer', 'xp' => 100],
                    ['title' => 'Grand Opening', 'description' => 'Feature is deployed to production', 'xp' => 200],
                    ['title' => 'Testimony', 'description' => 'Write documentation for your feature', 'xp' => 100],
                ],
                'total_steps' => 6,
                'reward_xp' => 1000,
                'reward_badge_slug' => 'master-architect',
                'reward_title' => 'Master Architect',
                'is_active' => true,
            ],
        ];
    }

    private function normalizeChain(array $chain): array
    {
        $metadata = [];
        $description = (string)($chain['description'] ?? '');

        if (preg_match('/\n\nMetadata:\s*(\{.*\})\s*$/s', $description, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
                $description = trim((string)preg_replace('/\n\nMetadata:\s*\{.*\}\s*$/s', '', $description));
            }
        }

        $steps = json_decode((string)($chain['steps'] ?? '[]'), true);
        if (!is_array($steps)) {
            $steps = [];
        }

        return [
            'id' => (int)($chain['id'] ?? 0),
            'slug' => (string)($chain['slug'] ?? ''),
            'name' => (string)($chain['name'] ?? ''),
            'description' => $description,
            'steps' => $steps,
            'total_steps' => (int)($chain['total_steps'] ?? count($steps)),
            'reward_xp' => (int)($chain['reward_xp'] ?? 0),
            'reward_badge_slug' => $chain['reward_badge_slug'] ?? null,
            'reward_title' => $chain['reward_title'] ?? null,
            'season_id' => isset($chain['season_id']) ? (int)$chain['season_id'] : null,
            'is_active' => (bool)($chain['is_active'] ?? true),
            'type' => $metadata['type'] ?? 'quest_chain',
            'labels' => is_array($metadata['labels'] ?? null) ? $metadata['labels'] : [],
            'entry_criteria' => is_array($metadata['entry_criteria'] ?? null) ? $metadata['entry_criteria'] : [],
            'go_no_go_gates' => is_array($metadata['go_no_go_gates'] ?? null) ? $metadata['go_no_go_gates'] : [],
        ];
    }
}
