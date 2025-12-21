<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Repositories\FeatureRequestRepository;
use App\Repositories\FeatureVoteRepository;
use App\Repositories\EggTransactionRepository;
use App\Repositories\EmailNotificationRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AdminController
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly FeatureRequestRepository $featureRepo,
        private readonly FeatureVoteRepository $voteRepo,
        private readonly EggTransactionRepository $eggRepo,
        private readonly EmailNotificationRepository $notificationRepo
    ) {}

    public function getPendingFeatures(Request $request, Response $response): void
    {
        try {
            $this->requireAdmin($request);
            
            $limit = (int)$request->getParam('limit', 50);
            $pendingFeatures = $this->featureRepo->findByStatus('pending', $limit);

            $response->success($pendingFeatures);

        } catch (\Exception $e) {
            $this->errorResponse($response, 'Failed to fetch pending features', $e, (int)$e->getCode() ?: 500);
        }
    }

    public function approveFeature(Request $request, Response $response): void
    {
        try {
            $adminId = $this->requireAdmin($request);
            $featureId = (int)$request->getParam('id');
            $data = $request->getBody();

            $featureArray = $this->featureRepo->findById($featureId);
            if (!$featureArray) {
                $this->errorResponse($response, 'Feature request not found', null, 404);
                return;
            }

            if ($featureArray['status'] !== 'pending') {
                $this->errorResponse($response, 'Feature is not in pending status', null, 400);
                return;
            }

            // Update feature status
            $this->featureRepo->update($featureId, [
                'status' => 'approved',
                'approved_by' => $adminId,
                'approval_notes' => $data['notes'] ?? null
            ]);

            // TODO: In a real system, we'd have a separate table or activity log for feature_approvals
            // For now, we'll continue using the notification system as the primary "log" or action trigger
            
            $this->scheduleNotification($featureArray['user_id'], 'feature_approved', [
                'feature_title' => $featureArray['title'],
                'feature_id' => $featureId,
                'approval_notes' => $data['notes'] ?? null
            ]);

            $updatedFeature = $this->featureRepo->findById($featureId);
            $response->success($updatedFeature, 'Feature approved successfully');

        } catch (\Exception $e) {
            $this->errorResponse($response, 'Failed to approve feature', $e, (int)$e->getCode() ?: 500);
        }
    }

    public function rejectFeature(Request $request, Response $response): void
    {
        try {
            $adminId = $this->requireAdmin($request);
            $featureId = (int)$request->getParam('id');
            $data = $request->getBody();

            $featureArray = $this->featureRepo->findById($featureId);
            if (!$featureArray) {
                $this->errorResponse($response, 'Feature request not found', null, 404);
                return;
            }

            if ($featureArray['status'] !== 'pending') {
                $this->errorResponse($response, 'Feature is not in pending status', null, 400);
                return;
            }

            // Update feature status
            $this->featureRepo->update($featureId, [
                'status' => 'rejected',
                'approved_by' => $adminId,
                'approval_notes' => $data['notes'] ?? null
            ]);

            // Log rejection via notification
            $this->scheduleNotification($featureArray['user_id'], 'feature_rejected', [
                'feature_title' => $featureArray['title'],
                'feature_id' => $featureId,
                'rejection_reason' => $data['notes'] ?? 'No reason provided'
            ]);

            $updatedFeature = $this->featureRepo->findById($featureId);
            $response->success($updatedFeature, 'Feature rejected');

        } catch (\Exception $e) {
            $this->errorResponse($response, 'Failed to reject feature', $e, (int)$e->getCode() ?: 500);
        }
    }

    public function updateFeatureStatus(Request $request, Response $response): void
    {
        try {
            $adminId = $this->requireAdmin($request);
            $featureId = (int)$request->getParam('id');
            $data = $request->getBody();

            $featureArray = $this->featureRepo->findById($featureId);
            if (!$featureArray) {
                $this->errorResponse($response, 'Feature request not found', null, 404);
                return;
            }

            $allowedStatuses = ['approved', 'open', 'planned', 'in_progress', 'completed', 'rejected'];
            if (!isset($data['status']) || !in_array($data['status'], $allowedStatuses)) {
                $this->errorResponse($response, 'Invalid status', null, 400);
                return;
            }

            $updateData = ['status' => $data['status']];
            if (isset($data['approval_notes'])) {
                $updateData['approval_notes'] = $data['approval_notes'];
            }
            
            $this->featureRepo->update($featureId, $updateData);

            $updatedFeature = $this->featureRepo->findById($featureId);
            $response->success($updatedFeature, 'Feature status updated successfully');

        } catch (\Exception $e) {
            $this->errorResponse($response, 'Failed to update feature status', $e, (int)$e->getCode() ?: 500);
        }
    }

    public function adjustUserEggs(Request $request, Response $response): void
    {
        try {
            $adminId = $this->requireAdmin($request);
            $userId = (int)$request->getParam('id');
            $data = $request->getBody();

            $userArray = $this->userRepo->findById($userId);
            if (!$userArray) {
                $this->errorResponse($response, 'User not found', null, 404);
                return;
            }

            if (!isset($data['amount']) || !is_numeric($data['amount'])) {
                $this->errorResponse($response, 'Valid amount is required', null, 400);
                return;
            }

            $amount = (int)$data['amount'];
            $reason = (string)($data['reason'] ?? 'Admin adjustment');

            // Record transaction
            $this->eggRepo->create([
                'user_id' => $userId,
                'amount' => $amount,
                'transaction_type' => 'admin_adjustment',
                'description' => $reason
            ]);

            // Update user balance would normally be handled by a service or trigger, 
            // but for now we'll do it manually in the repo if it's not handled.
            // Assuming userRepo->update or a specific balance method exists.
            
            $updatedUser = $this->userRepo->findById($userId);

            $response->success([
                'user_id' => $userId,
                'adjustment_amount' => $amount,
                'new_balance' => $updatedUser['egg_balance'] ?? 0,
                'reason' => $reason
            ], $amount > 0 ? 'Eggs added to user account' : 'Eggs deducted from user account');

        } catch (\Exception $e) {
            $this->errorResponse($response, 'Failed to adjust user eggs', $e, (int)$e->getCode() ?: 500);
        }
    }

    public function getAdminStats(Request $request, Response $response): void
    {
        try {
            $this->requireAdmin($request);

            $stats = [
                'users' => [
                    'total' => $this->userRepo->countAll(),
                    'verified' => $this->userRepo->countVerified(),
                    'admins' => $this->userRepo->countByRole('admin'),
                ],
                'features' => [
                    'total' => $this->featureRepo->countAll(),
                    'pending' => $this->featureRepo->countByStatus('pending'),
                    'approved' => $this->featureRepo->countByStatus('approved'),
                ],
                'eggs' => [
                    'total_spent' => $this->eggRepo->getTotalSpent(),
                    'total_earned' => $this->eggRepo->getTotalEarned(),
                ],
                'votes' => [
                    'total_votes' => $this->voteRepo->countAllVotes() ?? 0,
                    'most_voted_feature' => $this->getMostVotedFeature(),
                ]
            ];

            $response->success($stats);

        } catch (\Exception $e) {
            $this->errorResponse($response, 'Failed to fetch admin statistics', $e, (int)$e->getCode() ?: 500);
        }
    }

    public function getUserManagement(Request $request, Response $response): void
    {
        try {
            $this->requireAdmin($request);
            
            $limit = (int)$request->getParam('limit', 50);
            $search = (string)$request->getParam('search', '');
            $role = (string)$request->getParam('role', '');

            $users = $this->userRepo->findAll($limit, $search, $role);

            $response->success($users);

        } catch (\Exception $e) {
            $this->errorResponse($response, 'Failed to fetch users', $e, (int)$e->getCode() ?: 500);
        }
    }

    public function bulkApproveFeatures(Request $request, Response $response): void
    {
        try {
            $adminId = $this->requireAdmin($request);
            $data = $request->getBody();

            if (!isset($data['feature_ids']) || !is_array($data['feature_ids'])) {
                $this->errorResponse($response, 'feature_ids array is required', null, 400);
                return;
            }

            $approvedCount = 0;
            $errors = [];

            foreach ($data['feature_ids'] as $featureId) {
                try {
                    $featureArray = $this->featureRepo->findById((int)$featureId);
                    if (!$featureArray || $featureArray['status'] !== 'pending') {
                        $errors[] = "Feature {$featureId}: not found or not pending";
                        continue;
                    }

                    $this->featureRepo->update((int)$featureId, [
                        'status' => 'approved',
                        'approved_by' => $adminId,
                        'approval_notes' => $data['notes'] ?? 'Bulk approval'
                    ]);

                    $this->scheduleNotification($featureArray['user_id'], 'feature_approved', [
                        'feature_title' => $featureArray['title'],
                        'feature_id' => $featureId,
                        'approval_notes' => $data['notes'] ?? null
                    ]);

                    $approvedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Feature {$featureId}: " . $e->getMessage();
                }
            }

            $response->success([
                'approved_count' => $approvedCount,
                'total_requested' => count($data['feature_ids']),
                'errors' => $errors
            ], "Bulk approval completed. {$approvedCount} features approved.");

        } catch (\Exception $e) {
            $this->errorResponse($response, 'Bulk approval failed', $e, (int)$e->getCode() ?: 500);
        }
    }

    private function requireAdmin(Request $request): int
    {
        $userId = $this->getUserIdFromToken($request);
        
        $userArray = $this->userRepo->findById($userId);
        if (!$userArray || $userArray['role'] !== 'admin') {
            throw new \Exception('Admin access required', 403);
        }

        return $userId;
    }

    private function getUserIdFromToken(Request $request): int
    {
        $token = $request->getHeader('authorization');
        
        if (!$token || !preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            throw new \Exception('Authorization token required', 401);
        }

        $token = $matches[1];
        $secret = Config::get('jwt.secret');
        
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (int)$decoded->user_id;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token', 401);
        }
    }

    private function errorResponse(Response $response, string $message, ?\Exception $e, int $status = 500): void
    {
        $errorMsg = $message;
        if ($e) {
            $errorMsg .= ': ' . $e->getMessage();
        }
        $response->error($errorMsg, $status);
    }

    private function getMostVotedFeature(): ?array
    {
        $features = $this->featureRepo->findByStatus('approved', 1);
        $feature = $features[0] ?? null;

        return $feature ? [
            'id' => $feature['id'],
            'title' => $feature['title'],
            'total_eggs' => $feature['total_eggs'],
            'vote_count' => $feature['vote_count']
        ] : null;
    }

    private function scheduleNotification(int $userId, string $type, array $metadata): void
    {
        $this->notificationRepo->create([
            'user_id' => $userId,
            'type' => $type,
            'subject' => $this->getNotificationSubject($type),
            'message' => $this->getNotificationMessage($type, $metadata),
            'metadata' => $metadata,
            'status' => 'pending'
        ]);
    }

    private function getNotificationSubject(string $type): string
    {
        $subjects = [
            'feature_approved' => 'Your Feature Request Has Been Approved!',
            'feature_rejected' => 'Feature Request Update',
            'daily_reminder' => 'Don\'t Forget Your Daily Eggs!',
            'weekly_digest' => 'Weekly Feature Request Summary'
        ];

        return $subjects[$type] ?? 'WebHatchery Notification';
    }

    private function getNotificationMessage(string $type, array $metadata): string
    {
        switch ($type) {
            case 'feature_approved':
                return "Great news! Your feature request '{$metadata['feature_title']}' has been approved and is now available for voting.";
            case 'feature_rejected':
                return "Your feature request '{$metadata['feature_title']}' has been reviewed. Reason: " . ($metadata['rejection_reason'] ?? 'Not specified');
            case 'daily_reminder':
                return "Don't forget to claim your daily 100 eggs! Visit your dashboard to collect them.";
            default:
                return "You have a new notification from WebHatchery.";
        }
    }
}
