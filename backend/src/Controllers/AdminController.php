<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Models\FeatureRequest;
use App\Models\EggTransaction;
use App\Models\FeatureVote;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AdminController
{
    public function getPendingFeatures(Request $request, Response $response): Response
    {
        try {
            $this->requireAdmin($request);
            
            $queryParams = $request->getQueryParams();
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50;
            $sort_by = $queryParams['sort_by'] ?? 'created_at';
            $sort_direction = $queryParams['sort_direction'] ?? 'desc';

            $query = FeatureRequest::where('status', 'pending')
                ->with(['user:id,username,display_name,email', 'project:id,title'])
                ->orderBy($sort_by, $sort_direction);

            if ($limit > 0) {
                $query->limit($limit);
            }

            $pendingFeatures = $query->get()->map(function ($feature) {
                return $feature->toApiArray();
            });

            $payload = json_encode([
                'success' => true,
                'data' => $pendingFeatures,
                'count' => $pendingFeatures->count()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch pending features', $e);
        }
    }

    public function approveFeature(Request $request, Response $response, array $args): Response
    {
        try {
            $adminId = $this->requireAdmin($request);
            $featureId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            $feature = FeatureRequest::find($featureId);
            if (!$feature) {
                return $this->errorResponse($response, 'Feature request not found', null, 404);
            }

            if ($feature->status !== 'pending') {
                return $this->errorResponse($response, 'Feature is not in pending status', null, 400);
            }

            // Update feature status
            $feature->status = 'approved';
            $feature->approved_by = $adminId;
            $feature->approved_at = new \DateTime();
            $feature->approval_notes = $data['notes'] ?? null;
            $feature->save();

            // Log the approval action
            \Illuminate\Database\Capsule\Manager::table('feature_approvals')->insert([
                'feature_id' => $featureId,
                'admin_id' => $adminId,
                'action' => 'approve',
                'notes' => $data['notes'] ?? null,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime()
            ]);

            // TODO: Send notification to user
            $this->scheduleNotification($feature->user_id, 'feature_approved', [
                'feature_title' => $feature->title,
                'feature_id' => $feature->id,
                'approval_notes' => $data['notes'] ?? null
            ]);

            $payload = json_encode([
                'success' => true,
                'message' => 'Feature approved successfully',
                'data' => $feature->fresh(['user', 'project', 'approvedBy'])->toApiArray()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to approve feature', $e);
        }
    }

    public function rejectFeature(Request $request, Response $response, array $args): Response
    {
        try {
            $adminId = $this->requireAdmin($request);
            $featureId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            $feature = FeatureRequest::find($featureId);
            if (!$feature) {
                return $this->errorResponse($response, 'Feature request not found', null, 404);
            }

            if ($feature->status !== 'pending') {
                return $this->errorResponse($response, 'Feature is not in pending status', null, 400);
            }

            // Update feature status
            $feature->status = 'rejected';
            $feature->approved_by = $adminId;
            $feature->approved_at = new \DateTime();
            $feature->approval_notes = $data['notes'] ?? null;
            $feature->save();

            // Log the rejection action
            \Illuminate\Database\Capsule\Manager::table('feature_approvals')->insert([
                'feature_id' => $featureId,
                'admin_id' => $adminId,
                'action' => 'reject',
                'notes' => $data['notes'] ?? null,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime()
            ]);

            // TODO: Send notification to user
            $this->scheduleNotification($feature->user_id, 'feature_rejected', [
                'feature_title' => $feature->title,
                'feature_id' => $feature->id,
                'rejection_reason' => $data['notes'] ?? 'No reason provided'
            ]);

            $payload = json_encode([
                'success' => true,
                'message' => 'Feature rejected',
                'data' => $feature->fresh(['user', 'project', 'approvedBy'])->toApiArray()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to reject feature', $e);
        }
    }

    public function updateFeatureStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $adminId = $this->requireAdmin($request);
            $featureId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            $feature = FeatureRequest::find($featureId);
            if (!$feature) {
                return $this->errorResponse($response, 'Feature request not found', null, 404);
            }

            $allowedStatuses = ['approved', 'open', 'planned', 'in_progress', 'completed', 'rejected'];
            if (!isset($data['status']) || !in_array($data['status'], $allowedStatuses)) {
                return $this->errorResponse($response, 'Invalid status', null, 400);
            }

            $oldStatus = $feature->status;
            $feature->status = $data['status'];
            
            if (isset($data['approval_notes'])) {
                $feature->approval_notes = $data['approval_notes'];
            }
            
            $feature->save();

            // Log status change
            \Illuminate\Database\Capsule\Manager::table('feature_approvals')->insert([
                'feature_id' => $featureId,
                'admin_id' => $adminId,
                'action' => 'status_change',
                'notes' => "Status changed from {$oldStatus} to {$data['status']}. " . ($data['approval_notes'] ?? ''),
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime()
            ]);

            $payload = json_encode([
                'success' => true,
                'message' => 'Feature status updated successfully',
                'data' => $feature->fresh(['user', 'project', 'approvedBy'])->toApiArray()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to update feature status', $e);
        }
    }

    public function adjustUserEggs(Request $request, Response $response, array $args): Response
    {
        try {
            $adminId = $this->requireAdmin($request);
            $userId = (int)$args['id'];
            $data = json_decode($request->getBody()->getContents(), true);

            $user = User::find($userId);
            if (!$user) {
                return $this->errorResponse($response, 'User not found', null, 404);
            }

            if (!isset($data['amount']) || !is_numeric($data['amount'])) {
                return $this->errorResponse($response, 'Valid amount is required', null, 400);
            }

            $amount = (int)$data['amount'];
            $reason = $data['reason'] ?? 'Admin adjustment';

            if ($amount > 0) {
                $user->awardEggs($amount, 'admin_adjustment', $reason);
            } else {
                $user->spendEggs(abs($amount), $reason);
            }

            $admin = User::find($adminId);
            $adminName = $admin ? $admin->display_name : 'Admin';

            $payload = json_encode([
                'success' => true,
                'message' => $amount > 0 ? 'Eggs added to user account' : 'Eggs deducted from user account',
                'data' => [
                    'user_id' => $userId,
                    'adjustment_amount' => $amount,
                    'new_balance' => $user->fresh()->egg_balance,
                    'reason' => $reason,
                    'adjusted_by' => $adminName
                ]
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to adjust user eggs', $e);
        }
    }

    public function getAdminStats(Request $request, Response $response): Response
    {
        try {
            $this->requireAdmin($request);

            $stats = [
                'users' => [
                    'total' => User::count(),
                    'verified' => User::where('is_verified', true)->count(),
                    'admins' => User::where('role', 'admin')->count(),
                    'new_this_month' => User::where('created_at', '>=', date('Y-m-01'))->count(),
                ],
                'features' => [
                    'total' => FeatureRequest::count(),
                    'pending' => FeatureRequest::where('status', 'pending')->count(),
                    'approved' => FeatureRequest::where('status', 'approved')->count(),
                    'in_progress' => FeatureRequest::where('status', 'in_progress')->count(),
                    'completed' => FeatureRequest::where('status', 'completed')->count(),
                    'rejected' => FeatureRequest::where('status', 'rejected')->count(),
                ],
                'eggs' => [
                    'total_in_circulation' => User::sum('egg_balance'),
                    'total_spent' => EggTransaction::where('amount', '<', 0)->sum('amount') * -1,
                    'total_earned' => EggTransaction::where('amount', '>', 0)->sum('amount'),
                    'daily_rewards_claimed_today' => EggTransaction::where('transaction_type', 'daily_reward')
                        ->whereDate('created_at', date('Y-m-d'))
                        ->count(),
                ],
                'votes' => [
                    'total_votes' => FeatureVote::count(),
                    'total_eggs_allocated' => FeatureVote::sum('eggs_allocated'),
                    'unique_voters' => FeatureVote::distinct('user_id')->count(),
                    'most_voted_feature' => $this->getMostVotedFeature(),
                ],
                'recent_activity' => [
                    'new_features_today' => FeatureRequest::whereDate('created_at', date('Y-m-d'))->count(),
                    'votes_today' => FeatureVote::whereDate('created_at', date('Y-m-d'))->count(),
                    'eggs_spent_today' => EggTransaction::whereDate('created_at', date('Y-m-d'))
                        ->where('amount', '<', 0)
                        ->sum('amount') * -1,
                ]
            ];

            $payload = json_encode([
                'success' => true,
                'data' => $stats
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch admin statistics', $e);
        }
    }

    public function getUserManagement(Request $request, Response $response): Response
    {
        try {
            $this->requireAdmin($request);
            
            $queryParams = $request->getQueryParams();
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50;
            $search = $queryParams['search'] ?? null;
            $role = $queryParams['role'] ?? null;

            $query = User::query();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('display_name', 'like', "%{$search}%");
                });
            }

            if ($role) {
                $query->where('role', $role);
            }

            $query->orderBy('created_at', 'desc');

            if ($limit > 0) {
                $query->limit($limit);
            }

            $users = $query->get()->map(function ($user) {
                $userData = $user->toApiArray();
                $userData['features_count'] = FeatureRequest::where('user_id', $user->id)->count();
                $userData['votes_count'] = FeatureVote::where('user_id', $user->id)->count();
                $userData['eggs_spent'] = EggTransaction::where('user_id', $user->id)
                    ->where('amount', '<', 0)
                    ->sum('amount') * -1;
                return $userData;
            });

            $payload = json_encode([
                'success' => true,
                'data' => $users,
                'count' => $users->count()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch users', $e);
        }
    }

    public function bulkApproveFeatures(Request $request, Response $response): Response
    {
        try {
            $adminId = $this->requireAdmin($request);
            $data = json_decode($request->getBody()->getContents(), true);

            if (!isset($data['feature_ids']) || !is_array($data['feature_ids'])) {
                return $this->errorResponse($response, 'feature_ids array is required', null, 400);
            }

            $approvedCount = 0;
            $errors = [];

            foreach ($data['feature_ids'] as $featureId) {
                try {
                    $feature = FeatureRequest::find($featureId);
                    if (!$feature || $feature->status !== 'pending') {
                        $errors[] = "Feature {$featureId}: not found or not pending";
                        continue;
                    }

                    $feature->status = 'approved';
                    $feature->approved_by = $adminId;
                    $feature->approved_at = new \DateTime();
                    $feature->approval_notes = $data['notes'] ?? 'Bulk approval';
                    $feature->save();

                    \Illuminate\Database\Capsule\Manager::table('feature_approvals')->insert([
                        'feature_id' => $featureId,
                        'admin_id' => $adminId,
                        'action' => 'approve',
                        'notes' => 'Bulk approval: ' . ($data['notes'] ?? ''),
                        'created_at' => new \DateTime(),
                        'updated_at' => new \DateTime()
                    ]);

                    $this->scheduleNotification($feature->user_id, 'feature_approved', [
                        'feature_title' => $feature->title,
                        'feature_id' => $feature->id,
                        'approval_notes' => $data['notes'] ?? null
                    ]);

                    $approvedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Feature {$featureId}: " . $e->getMessage();
                }
            }

            $payload = json_encode([
                'success' => true,
                'message' => "Bulk approval completed. {$approvedCount} features approved.",
                'data' => [
                    'approved_count' => $approvedCount,
                    'total_requested' => count($data['feature_ids']),
                    'errors' => $errors
                ]
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Bulk approval failed', $e);
        }
    }

    private function requireAdmin(Request $request): int
    {
        $userId = $this->getUserIdFromToken($request);
        
        $user = User::find($userId);
        if (!$user || $user->role !== 'admin') {
            throw new \Exception('Admin access required', 403);
        }

        return $userId;
    }

    private function getUserIdFromToken(Request $request): int
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            throw new \Exception('Authorization token required', 401);
        }

        $token = $matches[1];
        $config = require __DIR__ . '/../../config.php';
        
        try {
            $decoded = JWT::decode($token, new Key($config['jwt']['secret'], 'HS256'));
            return $decoded->user_id;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token', 401);
        }
    }

    private function errorResponse(Response $response, string $message, ?\Exception $e, int $status = 500): Response
    {
        $payload = [
            'success' => false,
            'message' => $message
        ];

        if ($e) {
            $payload['error'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function getMostVotedFeature(): ?array
    {
        $feature = FeatureRequest::where('status', 'approved')
            ->orderBy('total_eggs', 'desc')
            ->with('user:id,username,display_name')
            ->first();

        return $feature ? [
            'id' => $feature->id,
            'title' => $feature->title,
            'total_eggs' => $feature->total_eggs,
            'vote_count' => $feature->vote_count,
            'user' => $feature->user ? $feature->user->display_name : 'Unknown'
        ] : null;
    }

    private function scheduleNotification(int $userId, string $type, array $metadata): void
    {
        // TODO: Implement email notification scheduling
        // This is a placeholder for the notification system
        \Illuminate\Database\Capsule\Manager::table('email_notifications')->insert([
            'user_id' => $userId,
            'type' => $type,
            'subject' => $this->getNotificationSubject($type),
            'message' => $this->getNotificationMessage($type, $metadata),
            'metadata' => json_encode($metadata),
            'status' => 'pending',
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime()
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
                return "Your feature request '{$metadata['feature_title']}' has been reviewed. Reason: {$metadata['rejection_reason']}";
            case 'daily_reminder':
                return "Don't forget to claim your daily 100 eggs! Visit your dashboard to collect them.";
            default:
                return "You have a new notification from WebHatchery.";
        }
    }
}