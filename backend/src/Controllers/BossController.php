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
}
