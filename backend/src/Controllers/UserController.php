<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Config\Config;
use App\Models\User;
use App\Models\EggTransaction;
use App\Models\FeatureRequest;
use App\Models\FeatureVote;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController
{
    public function getProfile(Request $request, Response $response): void
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $user = User::find($userId);
            if (!$user) {
                $response->error('User not found', 404);
                return;
            }

            $profile = $user->toApiArray();
            
            // Add additional profile stats
            $profile['stats'] = [
                'features_created' => FeatureRequest::where('user_id', $userId)->count(),
                'votes_cast' => FeatureVote::where('user_id', $userId)->count(),
                'eggs_spent' => EggTransaction::where('user_id', $userId)->where('amount', '<', 0)->sum('amount') * -1,
                'eggs_earned' => EggTransaction::where('user_id', $userId)->where('amount', '>', 0)->sum('amount'),
                'features_approved' => FeatureRequest::where('user_id', $userId)->where('status', 'approved')->count(),
                'features_completed' => FeatureRequest::where('user_id', $userId)->where('status', 'completed')->count(),
            ];

            $response->success($profile);

        } catch (\Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function updateProfile(Request $request, Response $response): void
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            $data = $request->getBody();
            
            $user = User::find($userId);
            if (!$user) {
                $response->error('User not found', 404);
                return;
            }

            // Update allowed fields
            $allowedFields = ['display_name', 'username'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    // Check if username is unique
                    if ($field === 'username' && $data[$field] !== $user->username) {
                        $existingUser = User::where('username', $data[$field])->where('id', '!=', $userId)->first();
                        if ($existingUser) {
                            $response->error('Username already taken', 400);
                            return;
                        }
                    }
                    
                    $user->{$field} = $data[$field];
                }
            }

            $user->save();
            $response->success($user->toApiArray(), 'Profile updated successfully');

        } catch (\Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function claimDailyEggs(Request $request, Response $response): void
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $user = User::find($userId);
            if (!$user) {
                $response->error('User not found', 404);
                return;
            }

            if (!$user->canClaimDailyReward()) {
                $response->error('Daily reward already claimed today', 400);
                return;
            }

            $claimed = $user->claimDailyReward(100);
            
            if ($claimed) {
                $response->success([
                    'eggs_earned' => 100,
                    'new_balance' => $user->fresh()->egg_balance,
                    'can_claim_tomorrow' => true
                ], 'Daily reward claimed! You earned 100 eggs.');
            } else {
                $response->error('Unable to claim daily reward');
            }

        } catch (\Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function getTransactions(Request $request, Response $response): void
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $limit = (int)$request->getQueryParam('limit', 50);
            $type = $request->getQueryParam('type');

            $query = EggTransaction::where('user_id', $userId)
                ->orderBy('created_at', 'desc');

            if ($type) {
                $query->where('transaction_type', $type);
            }

            if ($limit > 0) {
                $query->limit($limit);
            }

            $transactions = $query->get()->map(function ($transaction) {
                return $transaction->toApiArray();
            });

            $stats = EggTransaction::getTransactionStats($userId);

            $response->success([
                'transactions' => $transactions,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function getDashboard(Request $request, Response $response): void
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $user = User::find($userId);
            if (!$user) {
                $response->error('User not found', 404);
                return;
            }

            // Get user's recent features
            $myFeatures = FeatureRequest::where('user_id', $userId)
                ->with('project:id,title')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($feature) {
                    return $feature->toApiArray();
                });

            // Get user's recent votes
            $myVotes = FeatureVote::where('user_id', $userId)
                ->with('featureRequest:id,title,status,total_eggs')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($vote) {
                    $data = $vote->toApiArray();
                    if ($vote->featureRequest) {
                        $data['feature'] = [
                            'id' => $vote->featureRequest->id,
                            'title' => $vote->featureRequest->title,
                            'status' => $vote->featureRequest->status,
                            'total_eggs' => $vote->featureRequest->total_eggs
                        ];
                    }
                    return $data;
                });

            // Get recent transactions
            $recentTransactions = EggTransaction::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($transaction) {
                    return $transaction->toApiArray();
                });

            // Get top voted features (for inspiration)
            $popularFeatures = FeatureRequest::where('status', 'approved')
                ->orderBy('total_eggs', 'desc')
                ->limit(5)
                ->with('user:id,username,display_name', 'project:id,title')
                ->get()
                ->map(function ($feature) {
                    return $feature->toApiArray();
                });

            $dashboard = [
                'user' => $user->toApiArray(),
                'my_features' => $myFeatures,
                'my_votes' => $myVotes,
                'recent_transactions' => $recentTransactions,
                'popular_features' => $popularFeatures,
                'stats' => [
                    'total_features' => FeatureRequest::where('user_id', $userId)->count(),
                    'approved_features' => FeatureRequest::where('user_id', $userId)->where('status', 'approved')->count(),
                    'total_votes' => FeatureVote::where('user_id', $userId)->count(),
                    'eggs_invested' => FeatureVote::where('user_id', $userId)->sum('eggs_allocated'),
                ]
            ];

            $response->success($dashboard);

        } catch (\Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function register(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();
            
            // Validate required fields
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $response->error("Field '{$field}' is required", 400);
                    return;
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $response->error('Invalid email format', 400);
                return;
            }

            // Check if user already exists
            if (User::where('email', $data['email'])->exists()) {
                $response->error('Email already registered', 400);
                return;
            }

            if (User::where('username', $data['username'])->exists()) {
                $response->error('Username already taken', 400);
                return;
            }

            // Create user
            $user = User::createUser([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'display_name' => $data['display_name'] ?? $data['username']
            ]);

            $response->withStatus(201)->success($user->toApiArray(), 'Account created successfully! You received 500 welcome eggs.');

        } catch (\Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function login(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();
            
            if (!isset($data['email']) || !isset($data['password'])) {
                $response->error('Email and password are required', 400);
                return;
            }

            $user = User::where('email', $data['email'])->first();
            
            if (!$user || !password_verify($data['password'], $user->password_hash)) {
                $response->error('Invalid credentials', 401);
                return;
            }

            // Generate JWT token
            $secret = Config::get('jwt.secret');
            $expiration = Config::get('jwt.expiration');
            
            $payload_jwt = [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'iat' => time(),
                'exp' => time() + $expiration
            ];

            $token = JWT::encode($payload_jwt, $secret, 'HS256');

            $response->success([
                'user' => $user->toApiArray(),
                'token' => $token,
                'expires_at' => date('Y-m-d H:i:s', $payload_jwt['exp'])
            ], 'Login successful');

        } catch (\Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    public function deleteAccount(Request $request, Response $response): void
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $user = User::find($userId);
            if (!$user) {
                $response->error('User not found', 404);
                return;
            }

            // Delete related data first (due to foreign key constraints)
            FeatureRequest::where('user_id', $userId)->delete();
            FeatureVote::where('user_id', $userId)->delete();
            EggTransaction::where('user_id', $userId)->delete();
            
            // Delete the user
            $user->delete();

            $response->success(null, 'Account deleted successfully');

        } catch (\Exception $e) {
            $response->error($e->getMessage(), 500);
        }
    }

    private function getUserIdFromToken(Request $request): int
    {
        // Try to get from attribute first (set by middleware)
        $userId = $request->getAttribute('user_id');
        if ($userId) {
            return (int) $userId;
        }
        
        // Fallback to manual token handling if needed
        $token = $request->getHeader('authorization');
        if ($token && preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            $token = $matches[1];
        } else {
            throw new \Exception('Authorization token required');
        }

        $secret = Config::get('jwt.secret');
        
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (int) $decoded->user_id;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token');
        }
    }
}
