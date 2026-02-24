<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\AdventurerRepository;
use App\Services\GamificationService;
use RuntimeException;
use PDO;

final class GamificationWebhookController
{
    public function __construct(
        private readonly GamificationService $gamificationService,
        private readonly AdventurerRepository $adventurerRepository,
        private readonly PDO $db // Need PDO for the transaction/insert fallback if needed, but repo is better
    ) {}

    /**
     * Handle incoming GitHub webhook for Pull Request events
     */
    public function handlePullRequest(Request $request, Response $response): void
    {
        // Verify signature
        $secret = $_ENV['GITHUB_WEBHOOK_SECRET'] ?? throw new RuntimeException('GITHUB_WEBHOOK_SECRET environment variable is not set');
        if ($secret) {
            $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
            $payload = file_get_contents('php://input');

            if (!$this->verifySignature($payload, $signature, $secret)) {
                $response->withStatus(401)->json(['error' => 'Invalid signature']);
                return;
            }
        }

        $data = $request->getBody();

        // Only handle pull_request events
        $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown';
        if ($event !== 'pull_request') {
            $response->success(['message' => "Ignored event: $event", 'awarded' => false]);
            return;
        }

        $action = $data['action'] ?? null;
        $pullRequest = $data['pull_request'] ?? null;

        // We only care when a PR is merged (which comes as a 'closed' action with merged = true)
        if ($action !== 'closed' || empty($pullRequest) || empty($pullRequest['merged'])) {
            $response->success(['message' => 'Ignored PR event (not merged or not closed)', 'awarded' => false]);
            return;
        }

        $prUser = $pullRequest['user']['login'] ?? null;
        $prNumber = $pullRequest['number'] ?? null;
        $prLabels = $pullRequest['labels'] ?? [];

        if (!$prUser || !$prNumber) {
            $response->error('Missing PR user or number', 400);
            return;
        }

        // Calculate XP based on labels
        $baseXp = 50; // Merge bonus
        $labelXp = 0;
        $matchedLabels = [];

        foreach ($prLabels as $label) {
            $name = $label['name'] ?? '';
            if (str_starts_with($name, 'xp:')) {
                $amount = match($name) {
                    'xp:tiny' => 10,
                    'xp:small' => 50,
                    'xp:medium' => 200,
                    'xp:large' => 500,
                    'xp:epic' => 1000,
                    default => 0,
                };
                $labelXp += $amount;
                if ($amount > 0) {
                    $matchedLabels[] = ['name' => $name, 'amount' => $amount];
                }
            }
        }

        $totalXp = $baseXp + $labelXp;

        try {
            // Find or Create Adventurer
            $adventurer = $this->adventurerRepository->findByGitHubUsername($prUser);

            if (!$adventurer) {
                // Rather than inline PDO, we should ideally use the repository.
                // For direct porting, we'll keep the PDO insert or add a create method to repository if it existed.
                $stmt = $this->db->prepare("INSERT INTO adventurers (github_username, xp_total, level, class, created_at) VALUES (?, 0, 1, 'hatchling', NOW())");
                $stmt->execute([$prUser]);
                $adventurerId = (int)$this->db->lastInsertId();
            } else {
                $adventurerId = $adventurer->id;
            }

            // Award XP via Service
            $result = $this->gamificationService->awardXp($adventurerId, $totalXp, 'quest', "PR #$prNumber Merge");

            $response->success([
                'message' => 'XP Awarded successfully',
                'awarded' => true,
                'pr_number' => $prNumber,
                'user' => $prUser,
                'xp_awarded' => $totalXp,
                'labels_processed' => $matchedLabels,
                'new_total' => $result['new_xp'],
                'leveled_up' => $result['leveled_up'],
                'new_level' => $result['new_level'] ?? null,
                'badges_earned' => $result['badges_earned'] ?? []
            ]);

        } catch (\Exception $e) {
            $response->error('Error awarding XP: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify GitHub webhook signature
     */
    private function verifySignature(string $payload, string $signature, string $secret): bool
    {
        if (empty($signature)) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
