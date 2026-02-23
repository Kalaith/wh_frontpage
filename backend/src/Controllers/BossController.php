<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\BossRepository;
use App\Repositories\ProjectRepository;
use App\Models\Boss;

class BossController
{
    private BossRepository $repo;
    private ProjectRepository $projectRepo;

    public function __construct(BossRepository $repo, ProjectRepository $projectRepo)
    {
        $this->repo = $repo;
        $this->projectRepo = $projectRepo;
    }

    public function index(Request $request, Response $response): void
    {
        $bosses = $this->repo->getAllActive();

        $normalizedBosses = [];
        foreach ($bosses as $bossData) {
            if (!empty($bossData['project_id'])) {
                $project = $this->projectRepo->findById($bossData['project_id']);
                if ($project) {
                    $bossData['project_name'] = $project['title'];
                }
            }
            $normalizedBosses[] = $this->normalizeBoss($bossData);
        }

        $response->success($normalizedBosses);
    }

    /**
     * GET /api/admin/bosses
     * Admin-only list of all bosses.
     */
    public function adminIndex(Request $request, Response $response): void
    {
        $userRole = strtolower((string)$request->getAttribute('user_role', 'user'));
        if ($userRole !== 'admin') {
            $response->error('Admin access required', 403);
            return;
        }

        $bosses = $this->repo->getAll();
        $normalizedBosses = [];
        foreach ($bosses as $bossData) {
            if (!empty($bossData['project_id'])) {
                $project = $this->projectRepo->findById((int)$bossData['project_id']);
                if ($project) {
                    $bossData['project_name'] = $project['title'];
                }
            }
            $normalizedBosses[] = $this->normalizeBoss($bossData);
        }

        $response->success($normalizedBosses);
    }

    /**
     * POST /api/admin/bosses
     * Admin-only create boss.
     */
    public function adminCreate(Request $request, Response $response): void
    {
        $userRole = strtolower((string)$request->getAttribute('user_role', 'user'));
        if ($userRole !== 'admin') {
            $response->error('Admin access required', 403);
            return;
        }

        try {
            $data = $request->getBody();
            $name = trim((string)($data['name'] ?? ''));
            if ($name === '') {
                $response->error('Boss name is required', 400);
                return;
            }

            $db = \App\Repositories\DatabaseManager::getConnection();
            $stmt = $db->prepare(
                'INSERT INTO bosses
                    (github_issue_url, name, description, threat_level, status, project_id, season_id, hp_total, hp_current)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $hpTotal = max(1, (int)($data['hp_total'] ?? 8));
            $hpCurrent = (int)($data['hp_current'] ?? $hpTotal);
            if ($hpCurrent < 0) {
                $hpCurrent = 0;
            }
            if ($hpCurrent > $hpTotal) {
                $hpCurrent = $hpTotal;
            }

            $stmt->execute([
                $data['github_issue_url'] ?? null,
                $name,
                $this->buildBossDescription($data),
                max(1, min(5, (int)($data['threat_level'] ?? 3))),
                $this->normalizeBossStatus((string)($data['status'] ?? 'active')),
                isset($data['project_id']) && $data['project_id'] !== '' ? (int)$data['project_id'] : null,
                isset($data['season_id']) && $data['season_id'] !== '' ? (int)$data['season_id'] : null,
                $hpTotal,
                $hpCurrent,
            ]);

            $newId = (int)$db->lastInsertId();
            $all = $this->repo->getAll();
            foreach ($all as $bossData) {
                if ((int)$bossData['id'] === $newId) {
                    if (!empty($bossData['project_id'])) {
                        $project = $this->projectRepo->findById((int)$bossData['project_id']);
                        if ($project) {
                            $bossData['project_name'] = $project['title'];
                        }
                    }
                    $response->withStatus(201)->success($this->normalizeBoss($bossData), 'Boss created');
                    return;
                }
            }

            $response->success(['id' => $newId], 'Boss created');
        } catch (\Throwable $e) {
            $response->error('Failed to create boss: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/admin/bosses/{id}
     * Admin-only update boss.
     */
    public function adminUpdate(Request $request, Response $response): void
    {
        $userRole = strtolower((string)$request->getAttribute('user_role', 'user'));
        if ($userRole !== 'admin') {
            $response->error('Admin access required', 403);
            return;
        }

        $id = (int)$request->getParam('id', 0);
        if ($id <= 0) {
            $response->error('Invalid boss id', 400);
            return;
        }

        try {
            $db = \App\Repositories\DatabaseManager::getConnection();
            $find = $db->prepare('SELECT * FROM bosses WHERE id = ? LIMIT 1');
            $find->execute([$id]);
            $existing = $find->fetch(\PDO::FETCH_ASSOC);
            if (!$existing) {
                $response->error('Boss not found', 404);
                return;
            }

            $data = $request->getBody();
            $name = trim((string)($data['name'] ?? $existing['name']));
            if ($name === '') {
                $response->error('Boss name is required', 400);
                return;
            }

            $hpTotal = isset($data['hp_total']) ? max(1, (int)$data['hp_total']) : (int)$existing['hp_total'];
            $hpCurrent = isset($data['hp_current']) ? (int)$data['hp_current'] : (int)$existing['hp_current'];
            if ($hpCurrent < 0) {
                $hpCurrent = 0;
            }
            if ($hpCurrent > $hpTotal) {
                $hpCurrent = $hpTotal;
            }

            $status = $this->normalizeBossStatus((string)($data['status'] ?? $existing['status']));
            $defeatedAt = $status === 'defeated' ? date('Y-m-d H:i:s') : null;

            $update = $db->prepare(
                'UPDATE bosses
                 SET github_issue_url = ?,
                     name = ?,
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
                $data['github_issue_url'] ?? $existing['github_issue_url'],
                $name,
                $this->buildBossDescription($data, $existing['description'] ?? null),
                isset($data['threat_level']) ? max(1, min(5, (int)$data['threat_level'])) : (int)$existing['threat_level'],
                $status,
                array_key_exists('project_id', $data)
                    ? (($data['project_id'] === '' || $data['project_id'] === null) ? null : (int)$data['project_id'])
                    : (isset($existing['project_id']) ? (int)$existing['project_id'] : null),
                array_key_exists('season_id', $data)
                    ? (($data['season_id'] === '' || $data['season_id'] === null) ? null : (int)$data['season_id'])
                    : (isset($existing['season_id']) ? (int)$existing['season_id'] : null),
                $hpTotal,
                $hpCurrent,
                $defeatedAt,
                $id,
            ]);

            $find->execute([$id]);
            $updated = $find->fetch(\PDO::FETCH_ASSOC);
            if (!$updated) {
                $response->error('Failed to load updated boss', 500);
                return;
            }
            if (!empty($updated['project_id'])) {
                $project = $this->projectRepo->findById((int)$updated['project_id']);
                if ($project) {
                    $updated['project_name'] = $project['title'];
                }
            }
            $response->success($this->normalizeBoss($updated), 'Boss updated');
        } catch (\Throwable $e) {
            $response->error('Failed to update boss: ' . $e->getMessage(), 500);
        }
    }

    private function normalizeBoss(array $boss): array
    {
        $metadata = [];
        $description = (string)($boss['description'] ?? '');

        if (preg_match('/\n\nMetadata:\s*(\{.*\})\s*$/s', $description, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
                $description = trim((string)preg_replace('/\n\nMetadata:\s*\{.*\}\s*$/s', '', $description));
            }
        }

        $boss['description'] = $description;
        $boss['labels'] = is_array($metadata['labels'] ?? null) ? $metadata['labels'] : [];
        $boss['threat_type'] = $metadata['threat_type'] ?? null;
        $boss['deadline'] = $metadata['deadline'] ?? null;
        $boss['risk_level'] = $metadata['risk_level'] ?? null;
        $boss['rollback_plan'] = $metadata['rollback_plan'] ?? null;
        $boss['kill_criteria'] = is_array($metadata['kill_criteria'] ?? null) ? $metadata['kill_criteria'] : [];
        $boss['hp_tasks'] = is_array($metadata['hp_tasks'] ?? null) ? $metadata['hp_tasks'] : [];
        $boss['proof_required'] = is_array($metadata['proof_required'] ?? null) ? $metadata['proof_required'] : [];
        $boss['project_name'] = $boss['project_name'] ?? null;

        return $boss;
    }

    private function normalizeBossStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        if ($normalized !== 'active' && $normalized !== 'stabilizing' && $normalized !== 'defeated') {
            return 'active';
        }
        return $normalized;
    }

    private function buildBossDescription(array $payload, ?string $existingDescription = null): string
    {
        $existingBase = trim((string)$existingDescription);
        $existingMeta = [];
        if ($existingBase !== '' && preg_match('/\n\nMetadata:\s*(\{.*\})\s*$/s', $existingBase, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (is_array($decoded)) {
                $existingMeta = $decoded;
                $existingBase = trim((string)preg_replace('/\n\nMetadata:\s*\{.*\}\s*$/s', '', $existingBase));
            }
        }

        $base = trim((string)($payload['description'] ?? $existingBase));

        $metadata = [
            'labels' => is_array($payload['labels'] ?? null)
                ? array_values($payload['labels'])
                : (is_array($existingMeta['labels'] ?? null) ? $existingMeta['labels'] : []),
            'threat_type' => $payload['threat_type'] ?? ($existingMeta['threat_type'] ?? null),
            'deadline' => $payload['deadline'] ?? ($existingMeta['deadline'] ?? null),
            'risk_level' => $payload['risk_level'] ?? ($existingMeta['risk_level'] ?? null),
            'rollback_plan' => $payload['rollback_plan'] ?? ($existingMeta['rollback_plan'] ?? null),
            'kill_criteria' => is_array($payload['kill_criteria'] ?? null)
                ? array_values($payload['kill_criteria'])
                : (is_array($existingMeta['kill_criteria'] ?? null) ? $existingMeta['kill_criteria'] : []),
            'hp_tasks' => is_array($payload['hp_tasks'] ?? null)
                ? array_values($payload['hp_tasks'])
                : (is_array($existingMeta['hp_tasks'] ?? null) ? $existingMeta['hp_tasks'] : []),
            'proof_required' => is_array($payload['proof_required'] ?? null)
                ? array_values($payload['proof_required'])
                : (is_array($existingMeta['proof_required'] ?? null) ? $existingMeta['proof_required'] : []),
        ];

        if (
            empty($metadata['labels'])
            && $metadata['threat_type'] === null
            && $metadata['deadline'] === null
            && $metadata['risk_level'] === null
            && $metadata['rollback_plan'] === null
            && empty($metadata['kill_criteria'])
            && empty($metadata['hp_tasks'])
            && empty($metadata['proof_required'])
        ) {
            return $base;
        }

        return $base . "\n\nMetadata: " . json_encode($metadata, JSON_UNESCAPED_SLASHES);
    }
}
