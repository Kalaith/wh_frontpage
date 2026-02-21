<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\QuestChainRepository;

class QuestChainController
{
    private QuestChainRepository $questChainRepo;

    public function __construct(QuestChainRepository $questChainRepo)
    {
        $this->questChainRepo = $questChainRepo;
    }

    /**
     * GET /api/quest-chains
     * List all active quest chains with step details.
     */
    public function index(Request $request, Response $response): void
    {
        try {
            $chains = $this->questChainRepo->getAllActive();

            $result = array_map(fn ($chain) => $this->normalizeChain($chain), $chains ?: []);

            $response->success($result);
        } catch (\Exception $e) {
            // Return empty list
            $response->success([]);
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
            $chainData = $this->questChainRepo->findBySlug($slug);
            $chain = $chainData; // Ensure format match

            if (!$chain) {
                $response->notFound('Quest chain not found.');
                return;
            } else {
                $chain = $this->normalizeChain($chain);
            }

            $response->success($chain);
        } catch (\Exception $e) {
            $response->notFound('Quest chain not found.');
        }
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
