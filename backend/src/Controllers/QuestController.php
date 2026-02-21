<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProjectRepository;
use App\Repositories\QuestChainRepository;
use App\Repositories\DatabaseManager;
use App\Services\GitHubService;

class QuestController
{
    private GitHubService $github;
    private ProjectRepository $projectRepo;
    private QuestChainRepository $questChainRepo;
    private DatabaseManager $dbManager;

    public function __construct(
        GitHubService $github,
        ProjectRepository $projectRepo,
        QuestChainRepository $questChainRepo,
        DatabaseManager $dbManager
    ) {
        $this->github = $github;
        $this->projectRepo = $projectRepo;
        $this->questChainRepo = $questChainRepo;
        $this->dbManager = $dbManager;
    }

    public function index(Request $request, Response $response): void
    {
        // For starter quests, allow query params with sane defaults.
        $owner = $request->getParam('owner', 'Kalaith');
        $repo = $request->getParam('repo', 'wh_frontpage');
        $class = $request->getParam('class');
        $difficulty = $request->getParam('difficulty');
        $projectId = $request->getParam('project_id');

        $labels = ['quest'];
        if ($class) {
            $labels[] = "class:{$class}";
        }
        if ($difficulty) {
            $labels[] = "difficulty:{$difficulty}";
        }

        // Primary source: GitHub issues.
        try {
            $issues = $this->github->getIssues((string)$owner, (string)$repo, $labels);
            $quests = array_map([$this->github, 'parseQuest'], $issues);
            if (!empty($quests)) {
                $response->success($quests);
                return;
            }
        } catch (\Throwable $e) {
            // Fall through to DB-backed quests when GitHub is unavailable.
        }

        // Fallback source: DB quest chain steps.
        $dbQuests = $this->getQuestsFromDatabase(
            is_string($class) ? $class : null,
            $difficulty,
            $projectId !== null ? (int)$projectId : null
        );
        $response->success($dbQuests);
    }

    /**
     * POST /api/projects/{id}/quests
     * Create a quest under a specific project.
     * Permissions:
     * - admin: can post for any project
     * - guild_master: can post only for owned project
     */
    public function createForProject(Request $request, Response $response): void
    {
        if ($this->db === null) {
            $response->error('Database unavailable', 500);
            return;
        }

        $projectId = (int)$request->getParam('id', 0);
        if ($projectId <= 0) {
            $response->error('Invalid project id', 400);
            return;
        }

        $userId = (int)$request->getAttribute('user_id', 0);
        $userRole = strtolower((string)$request->getAttribute('user_role', 'user'));
        if ($userId <= 0) {
            $response->error('Authentication required', 401);
            return;
        }

        if ($userRole !== 'admin' && $userRole !== 'guild_master') {
            $response->error('Only admin or guild_master can post quests', 403);
            return;
        }

        try {
            $project = $this->projectRepo->findById($projectId);

            if (!$project) {
                $response->error('Project not found', 404);
                return;
            }

            if ($userRole === 'guild_master' && (int)($project['owner_user_id'] ?? 0) !== $userId) {
                $response->error('Guild master can post quests only for owned projects', 403);
                return;
            }

            $data = $request->getBody();
            $title = trim((string)($data['title'] ?? ''));
            $description = trim((string)($data['description'] ?? ''));
            if ($title === '' || $description === '') {
                $response->error('title and description are required', 400);
                return;
            }

            $this->dbManager->beginTransaction();

            $chainSlug = "project-{$projectId}-quests";
            $chain = $this->questChainRepo->findBySlug($chainSlug);

            if (!$chain) {
                $chainName = (string)$project['title'] . ': Project Quests';
                $chainDescription = 'Player-facing quests posted for this project.'
                    . "\n\nMetadata: " . json_encode([
                        'type' => 'quest_chain',
                        'labels' => ['type:quest', 'chain:project'],
                        'project_id' => $projectId,
                    ], JSON_UNESCAPED_SLASHES);

                $chainId = $this->questChainRepo->createChain(
                    $chainSlug,
                    $chainName,
                    $chainDescription,
                    [],
                    0
                );

                $steps = [];
            } else {
                $chainId = (int)$chain['id'];
                $steps = json_decode((string)($chain['steps'] ?? '[]'), true);
                if (!is_array($steps)) {
                    $steps = [];
                }
            }

            $nextNumber = count($steps) + 1;
            $questCode = trim((string)($data['id'] ?? "P{$projectId}-Q{$nextNumber}"));
            if ($questCode === '') {
                $questCode = "P{$projectId}-Q{$nextNumber}";
            }

            $step = [
                'id' => $questCode,
                'type' => 'Quest',
                'title' => $title,
                'description' => $description,
                'project_id' => $projectId,
                'created_by_user_id' => $userId,
                'rank_required' => (string)($data['rank_required'] ?? 'Iron'),
                'quest_level' => (int)($data['quest_level'] ?? 1),
                'dependency_type' => (string)($data['dependency_type'] ?? 'Independent'),
                'depends_on' => is_array($data['depends_on'] ?? null) ? array_values($data['depends_on']) : [],
                'unlock_condition' => (string)($data['unlock_condition'] ?? 'n/a'),
                'goal' => (string)($data['goal'] ?? $description),
                'player_steps' => is_array($data['player_steps'] ?? null) ? array_values($data['player_steps']) : [],
                'done_when' => is_array($data['done_when'] ?? null) ? array_values($data['done_when']) : [],
                'due_date' => (string)($data['due_date'] ?? ''),
                'proof_required' => is_array($data['proof_required'] ?? null) ? array_values($data['proof_required']) : ['proof:screenshot'],
                'rs_brief' => is_array($data['rs_brief'] ?? null) ? $data['rs_brief'] : null,
                'class_fantasy' => (string)($data['class_fantasy'] ?? ''),
                'class' => (string)($data['class'] ?? 'doc-sage'),
                'difficulty' => (int)($data['quest_level'] ?? 1),
                'xp' => (int)($data['xp'] ?? 20),
                'labels' => is_array($data['labels'] ?? null)
                    ? array_values($data['labels'])
                    : ['type:quest', 'chain:project', 'difficulty:' . (int)($data['quest_level'] ?? 1)],
            ];

            $steps[] = $step;

            $this->questChainRepo->updateSteps($chainId, $steps);

            $this->dbManager->commit();
            $response->withStatus(201)->success([
                'project_id' => $projectId,
                'chain_slug' => $chainSlug,
                'quest' => $step,
            ], 'Quest posted successfully');
        } catch (\Throwable $e) {
            if ($this->dbManager->inTransaction()) {
                $this->dbManager->rollBack();
            }
            $response->error('Failed to post quest: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Response $response, array $args = []): void
    {
        // TODO: Implement single quest view.
        $response->error('Not implemented yet', 501);
    }

    private function getQuestsFromDatabase(?string $class, mixed $difficulty, ?int $projectId = null): array
    {
        try {
            $chains = $this->questChainRepo->getAllActive();
        } catch (\Throwable $e) {
            return [];
        }

        $difficultyInt = $difficulty !== null ? (int)$difficulty : 0;
        $quests = [];
        $counter = 1;

        foreach ($chains as $chain) {
            $steps = json_decode((string)($chain['steps'] ?? '[]'), true);
            if (!is_array($steps)) {
                continue;
            }

            foreach ($steps as $idx => $step) {
                if (!is_array($step)) {
                    continue;
                }

                $stepClass = (string)($step['class'] ?? '');
                $stepDifficulty = (int)($step['difficulty'] ?? 0);
                $stepProjectId = isset($step['project_id']) ? (int)$step['project_id'] : null;

                if ($class && $stepClass !== $class) {
                    continue;
                }
                if ($difficultyInt > 0 && $stepDifficulty !== $difficultyInt) {
                    continue;
                }
                if ($projectId !== null && $projectId > 0 && $stepProjectId !== $projectId) {
                    continue;
                }

                $stepLabels = [];
                $rawLabels = $step['labels'] ?? [];
                if (is_array($rawLabels)) {
                    foreach ($rawLabels as $label) {
                        if (!is_string($label)) {
                            continue;
                        }
                        $stepLabels[] = [
                            'name' => $label,
                            'color' => $this->labelColor($label),
                        ];
                    }
                }

                if (empty($stepLabels)) {
                    $stepLabels = [
                        ['name' => 'quest', 'color' => '0E8A16'],
                        ['name' => "difficulty:{$stepDifficulty}", 'color' => '1D76DB'],
                        ['name' => "class:{$stepClass}", 'color' => '7057ff'],
                    ];
                }

                $quests[] = [
                    'id' => ((int)$chain['id'] * 1000) + $idx + 1,
                    'number' => $counter++,
                    'quest_code' => isset($step['id']) ? (string)$step['id'] : null,
                    'title' => (string)($step['title'] ?? 'Untitled Quest'),
                    'url' => '#',
                    'body' => (string)($step['description'] ?? ''),
                    'difficulty' => $stepDifficulty,
                    'class' => $stepClass,
                    'xp' => (int)($step['xp'] ?? 0),
                    'labels' => $stepLabels,
                    'class_fantasy' => isset($step['class_fantasy']) ? (string)$step['class_fantasy'] : null,
                    'rank_required' => isset($step['rank_required']) ? (string)$step['rank_required'] : null,
                    'quest_level' => isset($step['quest_level']) ? (int)$step['quest_level'] : null,
                    'dependency_type' => isset($step['dependency_type']) ? (string)$step['dependency_type'] : null,
                    'depends_on' => is_array($step['depends_on'] ?? null) ? array_values($step['depends_on']) : [],
                    'unlock_condition' => isset($step['unlock_condition']) ? (string)$step['unlock_condition'] : null,
                    'goal' => isset($step['goal']) ? (string)$step['goal'] : null,
                    'player_steps' => is_array($step['player_steps'] ?? null) ? array_values($step['player_steps']) : [],
                    'done_when' => is_array($step['done_when'] ?? null) ? array_values($step['done_when']) : [],
                    'rs_brief' => is_array($step['rs_brief'] ?? null) ? $step['rs_brief'] : null,
                    'specific' => isset($step['specific']) ? (string)$step['specific'] : null,
                    'metric_baseline' => isset($step['metric_baseline']) ? (string)$step['metric_baseline'] : null,
                    'metric_target' => isset($step['metric_target']) ? (string)$step['metric_target'] : null,
                    'in_scope' => is_array($step['in_scope'] ?? null) ? array_values($step['in_scope']) : [],
                    'out_of_scope' => is_array($step['out_of_scope'] ?? null) ? array_values($step['out_of_scope']) : [],
                    'risk_level' => isset($step['risk_level']) ? (string)$step['risk_level'] : null,
                    'rollback_plan' => isset($step['rollback_plan']) ? (string)$step['rollback_plan'] : null,
                    'due_date' => isset($step['due_date']) ? (string)$step['due_date'] : null,
                    'proof_required' => is_array($step['proof_required'] ?? null) ? array_values($step['proof_required']) : [],
                ];
            }
        }

        return $quests;
    }

    private function labelColor(string $label): string
    {
        if (str_starts_with($label, 'difficulty:1')) return '1D76DB';
        if (str_starts_with($label, 'difficulty:2')) return '006B75';
        if (str_starts_with($label, 'difficulty:3')) return 'FBCA04';
        if (str_starts_with($label, 'difficulty:4')) return 'D93F0B';
        if (str_starts_with($label, 'difficulty:5')) return 'B60205';
        if (str_starts_with($label, 'class:')) return '7057ff';
        if (str_starts_with($label, 'xp:')) return '5319e7';
        if (str_starts_with($label, 'type:raid')) return 'b60205';
        if (str_starts_with($label, 'type:boss')) return 'd73a4a';
        if (str_starts_with($label, 'type:quest')) return '0E8A16';
        if ($label === 'quest') return '0E8A16';
        if (str_starts_with($label, 'boss:')) return 'b60205';
        if (str_starts_with($label, 'raid:')) return 'b60205';
        return 'ededed';
    }
}
