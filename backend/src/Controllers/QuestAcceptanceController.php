<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\GamificationService;
use App\Services\RankService;
use App\Repositories\AdventurerRepository;
use PDO;

class QuestAcceptanceController
{
    private PDO $db;
    private RankService $rankService;
    private GamificationService $gamificationService;
    private AdventurerRepository $adventurerRepo;

    public function __construct(
        PDO $db,
        RankService $rankService,
        GamificationService $gamificationService,
        AdventurerRepository $adventurerRepo
    ) {
        $this->db = $db;
        $this->rankService = $rankService;
        $this->gamificationService = $gamificationService;
        $this->adventurerRepo = $adventurerRepo;
    }

    /**
     * POST /api/quests/{questRef}/accept
     * Accept a quest. Validates rank gating.
     */
    public function accept(Request $request, Response $response, array $args = []): void
    {
        $questRef = $args['questRef'] ?? '';
        if (empty($questRef)) {
            $response->error('Quest reference is required', 400);
            return;
        }

        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            $response->error('Authentication required', 401);
            return;
        }

        $adventurer = $this->adventurerRepo->findByUserId($userId);
        if (!$adventurer) {
            $response->error('Adventurer profile not found. Create a profile first.', 404);
            return;
        }

        // Check rank requirement from request body (frontend sends it)
        $body = $request->getBody();
        $requiredRank = $body['rank_required'] ?? null;
        if ($requiredRank && !$this->rankService->meetsRankRequirement($adventurer->id, $requiredRank)) {
            $response->error("Requires rank: {$requiredRank}. Your rank: " . $this->rankService->getAdventurerRank($adventurer->id), 403);
            return;
        }

        // Check if already accepted
        $stmt = $this->db->prepare(
            "SELECT id, status FROM quest_acceptances WHERE adventurer_id = ? AND quest_ref = ?"
        );
        $stmt->execute([$adventurer->id, $questRef]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $response->success(['status' => $existing['status'], 'message' => 'Quest already accepted']);
            return;
        }

        // Create acceptance
        $stmt = $this->db->prepare(
            "INSERT INTO quest_acceptances (adventurer_id, quest_ref, status, accepted_at)
             VALUES (?, ?, 'accepted', NOW())"
        );
        $stmt->execute([$adventurer->id, $questRef]);

        $response->success([
            'id' => (int) $this->db->lastInsertId(),
            'quest_ref' => $questRef,
            'status' => 'accepted',
            'message' => 'Quest accepted! Good luck, adventurer.',
        ]);
    }

    /**
     * GET /api/quests/my-quests
     * Get all quests accepted by the authenticated adventurer.
     */
    public function myQuests(Request $request, Response $response, array $args = []): void
    {
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            $response->error('Authentication required', 401);
            return;
        }

        $adventurer = $this->adventurerRepo->findByUserId($userId);
        if (!$adventurer) {
            $response->success([]);
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT id, quest_ref, status, accepted_at, submitted_at, completed_at
             FROM quest_acceptances
             WHERE adventurer_id = ?
             ORDER BY accepted_at DESC"
        );
        $stmt->execute([$adventurer->id]);
        $acceptances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also include rank progress
        $rankProgress = $this->rankService->getRankProgress($adventurer->id);

        $response->success([
            'acceptances' => $acceptances,
            'rank_progress' => $rankProgress,
        ]);
    }

    /**
     * POST /api/quests/{questRef}/submit
     * Mark a quest as submitted (proof placeholder for Step 2).
     */
    public function submit(Request $request, Response $response, array $args = []): void
    {
        $questRef = $args['questRef'] ?? '';
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            $response->error('Authentication required', 401);
            return;
        }

        $adventurer = $this->adventurerRepo->findByUserId($userId);
        if (!$adventurer) {
            $response->error('Adventurer profile not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT id, status FROM quest_acceptances WHERE adventurer_id = ? AND quest_ref = ?"
        );
        $stmt->execute([$adventurer->id, $questRef]);
        $acceptance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$acceptance) {
            $response->error('You have not accepted this quest', 404);
            return;
        }

        if ($acceptance['status'] !== 'accepted') {
            $response->error("Quest is already in status: {$acceptance['status']}", 400);
            return;
        }

        $stmt = $this->db->prepare(
            "UPDATE quest_acceptances SET status = 'submitted', submitted_at = NOW() WHERE id = ?"
        );
        $stmt->execute([$acceptance['id']]);

        $response->success([
            'quest_ref' => $questRef,
            'status' => 'submitted',
            'message' => 'Proof submitted! Awaiting review.',
        ]);
    }

    /**
     * POST /api/quests/{questRef}/complete
     * Admin/reviewer marks quest as completed, awards XP.
     */
    public function complete(Request $request, Response $response, array $args = []): void
    {
        $questRef = $args['questRef'] ?? '';
        $body = $request->getBody();
        $targetAdventurerId = (int)($body['adventurer_id'] ?? 0);
        $xpReward = (int)($body['xp'] ?? 0);
        $reviewNotes = (string)($body['review_notes'] ?? '');

        if (!$targetAdventurerId) {
            $response->error('adventurer_id is required', 400);
            return;
        }

        // Verify the reviewer is authenticated
        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            $response->error('Authentication required', 401);
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT id, status FROM quest_acceptances WHERE adventurer_id = ? AND quest_ref = ?"
        );
        $stmt->execute([$targetAdventurerId, $questRef]);
        $acceptance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$acceptance) {
            $response->error('Quest acceptance not found', 404);
            return;
        }

        if ($acceptance['status'] !== 'submitted') {
            $response->error("Quest must be in 'submitted' status to complete. Current: {$acceptance['status']}", 400);
            return;
        }

        // Get reviewer adventurer (if exists)
        $reviewer = $this->adventurerRepo->findByUserId($userId);
        $reviewerAdventurerId = $reviewer ? $reviewer->id : null;

        // Mark completed
        $stmt = $this->db->prepare(
            "UPDATE quest_acceptances
             SET status = 'completed', completed_at = NOW(), reviewer_adventurer_id = ?, review_notes = ?
             WHERE id = ?"
        );
        $stmt->execute([$reviewerAdventurerId, $reviewNotes, $acceptance['id']]);

        // Award XP to the quest completer
        $xpResult = null;
        if ($xpReward > 0) {
            try {
                $xpResult = $this->gamificationService->awardXp(
                    $targetAdventurerId,
                    $xpReward,
                    'quest',
                    $questRef
                );
            } catch (\Throwable $e) {
                // Log but don't fail the completion
            }
        }

        // Recalculate rank
        $rankResult = $this->rankService->recalculateRank($targetAdventurerId);

        $response->success([
            'quest_ref' => $questRef,
            'status' => 'completed',
            'xp_awarded' => $xpResult,
            'rank' => $rankResult,
            'message' => 'Quest completed! XP awarded.',
        ]);
    }

    /**
     * POST /api/quests/{questRef}/review
     * Peer review: Silver+ adventurers review Iron submissions for bonus XP.
     */
    public function review(Request $request, Response $response, array $args = []): void
    {
        $questRef = $args['questRef'] ?? '';
        $body = $request->getBody();
        $targetAdventurerId = (int)($body['adventurer_id'] ?? 0);
        $approved = (bool)($body['approved'] ?? false);
        $reviewNotes = (string)($body['review_notes'] ?? '');
        $xpReward = (int)($body['xp'] ?? 0);

        $userId = $request->getAttribute('user_id');
        if (!$userId) {
            $response->error('Authentication required', 401);
            return;
        }

        $reviewer = $this->adventurerRepo->findByUserId($userId);
        if (!$reviewer) {
            $response->error('Reviewer adventurer profile not found', 404);
            return;
        }

        // Reviewer must be Silver+ rank
        if (!$this->rankService->meetsRankRequirement($reviewer->id, 'Silver')) {
            $response->error('You must be Silver rank or higher to review quests', 403);
            return;
        }

        // Cannot review own quest
        if ($reviewer->id === $targetAdventurerId) {
            $response->error('You cannot review your own quest', 400);
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT id, status FROM quest_acceptances WHERE adventurer_id = ? AND quest_ref = ?"
        );
        $stmt->execute([$targetAdventurerId, $questRef]);
        $acceptance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$acceptance || $acceptance['status'] !== 'submitted') {
            $response->error('Quest must be in submitted status for review', 400);
            return;
        }

        $newStatus = $approved ? 'completed' : 'rejected';

        $stmt = $this->db->prepare(
            "UPDATE quest_acceptances
             SET status = ?, completed_at = IF(? = 'completed', NOW(), NULL), reviewer_adventurer_id = ?, review_notes = ?
             WHERE id = ?"
        );
        $stmt->execute([$newStatus, $newStatus, $reviewer->id, $reviewNotes, $acceptance['id']]);

        // Award XP to completer if approved
        if ($approved && $xpReward > 0) {
            try {
                $this->gamificationService->awardXp($targetAdventurerId, $xpReward, 'quest', $questRef);
                $this->rankService->recalculateRank($targetAdventurerId);
            } catch (\Throwable $e) {
                // Log but don't fail
            }

            // Bonus XP to reviewer (10% of quest XP, min 5)
            $reviewerBonus = max(5, (int) round($xpReward * 0.1));
            try {
                $this->gamificationService->awardXp($reviewer->id, $reviewerBonus, 'review', "reviewed:{$questRef}");
            } catch (\Throwable $e) {
                // Log but don't fail
            }
        }

        $response->success([
            'quest_ref' => $questRef,
            'status' => $newStatus,
            'message' => $approved ? 'Quest approved and completed!' : 'Quest rejected. Adventurer can resubmit.',
        ]);
    }
}
