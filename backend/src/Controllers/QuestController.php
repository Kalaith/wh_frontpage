<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProjectRepository;
use App\Repositories\QuestChainRepository;
use App\Repositories\DatabaseManager;
use App\Services\GitHubService;
use PDO;

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

    /**
     * POST /api/admin/quests/import-seed
     * Import quest seed JSON payload without shell/DB access.
     * Admin only.
     */
    public function importSeed(Request $request, Response $response): void
    {
        $userRole = strtolower((string)$request->getAttribute('user_role', 'user'));
        if ($userRole !== 'admin') {
            $response->error('Admin access required', 403);
            return;
        }

        try {
            $body = $request->getBody();
            $seedInput = $body['seed'] ?? $body;
            $seed = $this->normalizeSeedPayload($seedInput);

            if (!is_array($seed)) {
                $response->error('Invalid seed payload. Provide a JSON object or a JSON string.', 400);
                return;
            }

            $this->validateSeedPayload($seed);

            $clearExisting = filter_var((string)($body['clear_existing'] ?? 'false'), FILTER_VALIDATE_BOOLEAN);

            $this->dbManager->beginTransaction();
            $db = DatabaseManager::getConnection();

            $projectId = $this->resolveProjectIdFromSeed($db, $seed['habitat']);
            if ($projectId === null) {
                throw new \RuntimeException('Could not resolve project id from habitat.project_path or habitat.project_title');
            }

            $seasonId = $this->upsertSeasonFromSeed($db, $seed['season']);

            $chains = [];
            foreach ($seed['quest_chains'] as $chain) {
                if (is_array($chain)) {
                    $chain['type'] = 'quest_chain';
                    $chains[] = $chain;
                }
            }
            foreach ($seed['raids'] as $raid) {
                if (is_array($raid)) {
                    $raid['type'] = 'raid';
                    $chains[] = $raid;
                }
            }

            if ($clearExisting) {
                $this->clearExistingSeedData($db, $chains, $seed['bosses'], $projectId);
            }

            $chainsUpserted = 0;
            foreach ($chains as $chain) {
                $this->upsertQuestChainFromSeed($db, $chain, $seasonId);
                $chainsUpserted++;
            }

            $bossesUpserted = 0;
            foreach ($seed['bosses'] as $boss) {
                if (!is_array($boss)) {
                    continue;
                }
                $this->upsertBossFromSeed($db, $boss, $projectId, $seasonId);
                $bossesUpserted++;
            }

            $this->dbManager->commit();
            $response->success([
                'project_id' => $projectId,
                'season_id' => $seasonId,
                'chains_upserted' => $chainsUpserted,
                'bosses_upserted' => $bossesUpserted,
                'clear_existing' => $clearExisting,
            ], 'Quest seed imported successfully');
        } catch (\Throwable $e) {
            if ($this->dbManager->inTransaction()) {
                $this->dbManager->rollBack();
            }
            $response->error('Failed to import quest seed: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Response $response, array $args = []): void
    {
        // TODO: Implement single quest view.
        $response->error('Not implemented yet', 501);
    }

    /**
     * PUT /api/admin/quest-chains/{slug}/steps/{stepId}
     * Update a single quest step in a quest chain.
     * Admin only.
     */
    public function updateQuestStep(Request $request, Response $response): void
    {
        $userRole = strtolower((string)$request->getAttribute('user_role', 'user'));
        if ($userRole !== 'admin') {
            $response->error('Admin access required', 403);
            return;
        }

        $slug = trim((string)$request->getParam('slug', ''));
        $stepId = trim((string)$request->getParam('stepId', ''));
        if ($slug === '' || $stepId === '') {
            $response->error('slug and stepId are required', 400);
            return;
        }

        try {
            $chain = $this->questChainRepo->findBySlug($slug);
            if (!$chain) {
                $response->error('Quest chain not found', 404);
                return;
            }

            $steps = json_decode((string)($chain['steps'] ?? '[]'), true);
            if (!is_array($steps)) {
                $steps = [];
            }

            $index = null;
            foreach ($steps as $i => $step) {
                if (!is_array($step)) {
                    continue;
                }
                if ((string)($step['id'] ?? '') === $stepId) {
                    $index = $i;
                    break;
                }
            }

            if ($index === null && ctype_digit($stepId)) {
                $numericIndex = (int)$stepId;
                if (isset($steps[$numericIndex]) && is_array($steps[$numericIndex])) {
                    $index = $numericIndex;
                }
            }

            if ($index === null) {
                $response->error('Quest step not found in chain', 404);
                return;
            }

            $updates = $request->getBody();
            if (!is_array($updates)) {
                $response->error('Invalid request body', 400);
                return;
            }

            $allowedFields = [
                'id',
                'title',
                'description',
                'rank_required',
                'quest_level',
                'dependency_type',
                'depends_on',
                'unlock_condition',
                'goal',
                'player_steps',
                'done_when',
                'due_date',
                'proof_required',
                'rs_brief',
                'class_fantasy',
                'class',
                'difficulty',
                'xp',
                'labels',
            ];

            $updatedStep = $steps[$index];
            foreach ($allowedFields as $field) {
                if (!array_key_exists($field, $updates)) {
                    continue;
                }

                $value = $updates[$field];
                if (in_array($field, ['depends_on', 'player_steps', 'done_when', 'proof_required', 'labels'], true)) {
                    $updatedStep[$field] = is_array($value) ? array_values($value) : [];
                } elseif ($field === 'rs_brief') {
                    $updatedStep[$field] = is_array($value) ? $value : null;
                } elseif (in_array($field, ['quest_level', 'difficulty', 'xp'], true)) {
                    $updatedStep[$field] = (int)$value;
                } else {
                    $updatedStep[$field] = is_string($value) ? trim($value) : $value;
                }
            }

            $title = trim((string)($updatedStep['title'] ?? ''));
            $description = trim((string)($updatedStep['description'] ?? ''));
            if ($title === '' || $description === '') {
                $response->error('Step title and description are required', 400);
                return;
            }

            $steps[$index] = $updatedStep;
            $this->questChainRepo->updateSteps((int)$chain['id'], $steps);

            $response->success([
                'chain_slug' => $slug,
                'step' => $updatedStep,
            ], 'Quest step updated');
        } catch (\Throwable $e) {
            $response->error('Failed to update quest step: ' . $e->getMessage(), 500);
        }
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

    private function normalizeSeedPayload(mixed $seedInput): ?array
    {
        if (is_array($seedInput)) {
            return $seedInput;
        }

        if (is_string($seedInput) && trim($seedInput) !== '') {
            $decoded = json_decode($seedInput, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function validateSeedPayload(array $seed): void
    {
        $requiredTopKeys = ['habitat', 'season', 'quest_chains', 'raids', 'bosses'];
        foreach ($requiredTopKeys as $key) {
            if (!array_key_exists($key, $seed)) {
                throw new \RuntimeException("Missing required seed key: {$key}");
            }
        }

        if (!is_array($seed['habitat']) || !is_array($seed['season'])) {
            throw new \RuntimeException('habitat and season must be objects');
        }

        if (!is_array($seed['quest_chains']) || !is_array($seed['raids']) || !is_array($seed['bosses'])) {
            throw new \RuntimeException('quest_chains, raids, and bosses must be arrays');
        }
    }

    private function resolveProjectIdFromSeed(PDO $db, array $habitat): ?int
    {
        $path = isset($habitat['project_path']) ? trim((string)$habitat['project_path']) : '';
        if ($path !== '') {
            $stmt = $db->prepare('SELECT id FROM projects WHERE path = ? LIMIT 1');
            $stmt->execute([$path]);
            $row = $stmt->fetch();
            if ($row) {
                return (int)$row['id'];
            }
        }

        $title = isset($habitat['project_title']) ? trim((string)$habitat['project_title']) : '';
        if ($title !== '') {
            $stmt = $db->prepare('SELECT id FROM projects WHERE title = ? LIMIT 1');
            $stmt->execute([$title]);
            $row = $stmt->fetch();
            if ($row) {
                return (int)$row['id'];
            }
        }

        return null;
    }

    private function upsertSeasonFromSeed(PDO $db, array $season): int
    {
        $slug = trim((string)($season['slug'] ?? ''));
        $name = trim((string)($season['name'] ?? ''));
        $startsAt = trim((string)($season['starts_at'] ?? ''));
        $endsAt = trim((string)($season['ends_at'] ?? ''));

        if ($slug === '' || $name === '' || $startsAt === '' || $endsAt === '') {
            throw new \RuntimeException('season must include slug, name, starts_at, ends_at');
        }

        $stmt = $db->prepare(
            'INSERT INTO seasons (name, slug, starts_at, ends_at, is_active, path_chosen)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                starts_at = VALUES(starts_at),
                ends_at = VALUES(ends_at),
                is_active = VALUES(is_active),
                path_chosen = VALUES(path_chosen)'
        );
        $stmt->execute([
            $name,
            $slug,
            $startsAt,
            $endsAt,
            !empty($season['is_active']) ? 1 : 0,
            $season['path_chosen'] ?? null,
        ]);

        $find = $db->prepare('SELECT id FROM seasons WHERE slug = ? LIMIT 1');
        $find->execute([$slug]);
        $row = $find->fetch();
        if (!$row) {
            throw new \RuntimeException('Failed to resolve season id after upsert');
        }

        return (int)$row['id'];
    }

    private function clearExistingSeedData(PDO $db, array $chains, array $bosses, int $projectId): void
    {
        $chainSlugs = [];
        $questRefs = [];
        foreach ($chains as $chain) {
            if (!is_array($chain)) {
                continue;
            }
            $slug = trim((string)($chain['slug'] ?? ''));
            if ($slug !== '') {
                $chainSlugs[] = $slug;
            }
            $steps = $chain['steps'] ?? [];
            if (is_array($steps)) {
                foreach ($steps as $step) {
                    if (!is_array($step)) {
                        continue;
                    }
                    $questRef = trim((string)($step['id'] ?? ''));
                    if ($questRef !== '') {
                        $questRefs[] = $questRef;
                    }
                }
            }
        }

        $bossNames = [];
        foreach ($bosses as $boss) {
            if (!is_array($boss)) {
                continue;
            }
            $name = trim((string)($boss['name'] ?? ''));
            if ($name !== '') {
                $bossNames[] = $name;
            }
        }

        $chainSlugs = array_values(array_unique($chainSlugs));
        $questRefs = array_values(array_unique($questRefs));
        $bossNames = array_values(array_unique($bossNames));

        if (!empty($questRefs)) {
            $placeholders = implode(',', array_fill(0, count($questRefs), '?'));
            $stmt = $db->prepare("DELETE FROM quest_acceptances WHERE quest_ref IN ({$placeholders})");
            $stmt->execute($questRefs);
        }

        if (!empty($chainSlugs)) {
            $placeholders = implode(',', array_fill(0, count($chainSlugs), '?'));
            $stmt = $db->prepare("DELETE FROM quest_chain_progress WHERE chain_id IN (SELECT id FROM quest_chains WHERE slug IN ({$placeholders}))");
            $stmt->execute($chainSlugs);

            $stmt = $db->prepare("DELETE FROM quest_chains WHERE slug IN ({$placeholders})");
            $stmt->execute($chainSlugs);
        }

        if (!empty($bossNames)) {
            $placeholders = implode(',', array_fill(0, count($bossNames), '?'));
            $params = array_merge([$projectId], $bossNames);
            $stmt = $db->prepare("DELETE FROM bosses WHERE project_id <=> ? AND name IN ({$placeholders})");
            $stmt->execute($params);
        }
    }

    private function upsertQuestChainFromSeed(PDO $db, array $chain, int $seasonId): void
    {
        $slug = trim((string)($chain['slug'] ?? ''));
        $name = trim((string)($chain['name'] ?? ''));
        if ($slug === '' || $name === '') {
            throw new \RuntimeException('Each quest_chain/raid requires slug and name');
        }

        $steps = $chain['steps'] ?? [];
        if (!is_array($steps)) {
            throw new \RuntimeException("Chain {$slug} steps must be an array");
        }

        $description = trim((string)($chain['description'] ?? ''));
        $metadata = [
            'type' => $chain['type'] ?? 'quest_chain',
            'labels' => is_array($chain['labels'] ?? null) ? array_values($chain['labels']) : [],
            'entry_criteria' => is_array($chain['entry_criteria'] ?? null) ? array_values($chain['entry_criteria']) : [],
            'go_no_go_gates' => is_array($chain['go_no_go_gates'] ?? null) ? array_values($chain['go_no_go_gates']) : [],
        ];

        $descriptionWithMeta = $description;
        if (!empty($metadata['labels']) || !empty($metadata['entry_criteria']) || !empty($metadata['go_no_go_gates'])) {
            $descriptionWithMeta .= "\n\nMetadata: " . json_encode($metadata, JSON_UNESCAPED_SLASHES);
        }

        $stmt = $db->prepare(
            'INSERT INTO quest_chains
                (slug, name, description, steps, total_steps, reward_xp, reward_badge_slug, reward_title, season_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                description = VALUES(description),
                steps = VALUES(steps),
                total_steps = VALUES(total_steps),
                reward_xp = VALUES(reward_xp),
                reward_badge_slug = VALUES(reward_badge_slug),
                reward_title = VALUES(reward_title),
                season_id = VALUES(season_id),
                is_active = VALUES(is_active)'
        );

        $stmt->execute([
            $slug,
            $name,
            $descriptionWithMeta,
            json_encode($steps, JSON_UNESCAPED_SLASHES),
            count($steps),
            (int)($chain['reward_xp'] ?? 0),
            $chain['reward_badge_slug'] ?? null,
            $chain['reward_title'] ?? null,
            $seasonId,
            !empty($chain['is_active']) ? 1 : 0,
        ]);
    }

    private function upsertBossFromSeed(PDO $db, array $boss, int $projectId, int $seasonId): void
    {
        $name = trim((string)($boss['name'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('Each boss requires a name');
        }

        $description = $this->buildBossDescriptionFromSeed($boss);
        $defeatedAt = (($boss['status'] ?? 'active') === 'defeated') ? date('Y-m-d H:i:s') : null;

        $find = $db->prepare('SELECT id FROM bosses WHERE project_id <=> ? AND name = ? LIMIT 1');
        $find->execute([$projectId, $name]);
        $existing = $find->fetch();

        if ($existing) {
            $update = $db->prepare(
                'UPDATE bosses
                 SET github_issue_url = ?,
                     description = ?,
                     threat_level = ?,
                     status = ?,
                     project_id = ?,
                     season_id = ?,
                     hp_total = ?,
                     hp_current = ?,
                     defeated_at = ?
                 WHERE id = ?'
            );
            $update->execute([
                $boss['github_issue_url'] ?? null,
                $description,
                (int)($boss['threat_level'] ?? 4),
                $boss['status'] ?? 'active',
                $projectId,
                $seasonId,
                (int)($boss['hp_total'] ?? 8),
                (int)($boss['hp_current'] ?? ($boss['hp_total'] ?? 8)),
                $defeatedAt,
                (int)$existing['id'],
            ]);
            return;
        }

        $insert = $db->prepare(
            'INSERT INTO bosses
                (github_issue_url, name, description, threat_level, status, project_id, season_id, hp_total, hp_current, defeated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            $boss['github_issue_url'] ?? null,
            $name,
            $description,
            (int)($boss['threat_level'] ?? 4),
            $boss['status'] ?? 'active',
            $projectId,
            $seasonId,
            (int)($boss['hp_total'] ?? 8),
            (int)($boss['hp_current'] ?? ($boss['hp_total'] ?? 8)),
            $defeatedAt,
        ]);
    }

    private function buildBossDescriptionFromSeed(array $boss): string
    {
        $base = trim((string)($boss['description'] ?? ''));
        $metadata = [
            'id' => $boss['id'] ?? null,
            'labels' => is_array($boss['labels'] ?? null) ? array_values($boss['labels']) : [],
            'threat_type' => $boss['threat_type'] ?? null,
            'deadline' => $boss['deadline'] ?? null,
            'risk_level' => $boss['risk_level'] ?? null,
            'rollback_plan' => $boss['rollback_plan'] ?? null,
            'kill_criteria' => is_array($boss['kill_criteria'] ?? null) ? array_values($boss['kill_criteria']) : [],
            'hp_tasks' => is_array($boss['hp_tasks'] ?? null) ? array_values($boss['hp_tasks']) : [],
            'proof_required' => is_array($boss['proof_required'] ?? null) ? array_values($boss['proof_required']) : [],
        ];

        return $base . "\n\nMetadata: " . json_encode($metadata, JSON_UNESCAPED_SLASHES);
    }
}
