<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;
use App\Models\EggTransaction;
use App\Models\FeatureRequest;
use App\Models\FeatureVote;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController
{
    public function getProfile(Request $request, Response $response): Response
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $user = User::find($userId);
            if (!$user) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
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

            $payload = json_encode([
                'success' => true,
                'data' => $profile
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to fetch user profile',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            $data = json_decode($request->getBody()->getContents(), true);
            
            $user = User::find($userId);
            if (!$user) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Update allowed fields
            $allowedFields = ['display_name', 'username'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    // Check if username is unique
                    if ($field === 'username' && $data[$field] !== $user->username) {
                        $existingUser = User::where('username', $data[$field])->where('id', '!=', $userId)->first();
                        if ($existingUser) {
                            $payload = json_encode([
                                'success' => false,
                                'message' => 'Username already taken'
                            ]);
                            $response->getBody()->write($payload);
                            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                        }
                    }
                    
                    $user->{$field} = $data[$field];
                }
            }

            $user->save();

            $payload = json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user->toApiArray()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function claimDailyEggs(Request $request, Response $response): Response
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $user = User::find($userId);
            if (!$user) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if (!$user->canClaimDailyReward()) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Daily reward already claimed today'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $claimed = $user->claimDailyReward(100);
            
            if ($claimed) {
                $payload = json_encode([
                    'success' => true,
                    'message' => 'Daily reward claimed! You earned 100 eggs.',
                    'data' => [
                        'eggs_earned' => 100,
                        'new_balance' => $user->fresh()->egg_balance,
                        'can_claim_tomorrow' => true
                    ]
                ]);
            } else {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Unable to claim daily reward'
                ]);
            }

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to claim daily eggs',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getTransactions(Request $request, Response $response): Response
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            $queryParams = $request->getQueryParams();
            
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50;
            $type = $queryParams['type'] ?? null;

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

            $payload = json_encode([
                'success' => true,
                'data' => [
                    'transactions' => $transactions,
                    'stats' => $stats
                ]
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to fetch transactions',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getDashboard(Request $request, Response $response): Response
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $user = User::find($userId);
            if (!$user) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
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

            $payload = json_encode([
                'success' => true,
                'data' => $dashboard
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function register(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate required fields
            $required = ['username', 'email', 'password'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $payload = json_encode([
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ]);
                    $response->getBody()->write($payload);
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Invalid email format'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Check if user already exists
            if (User::where('email', $data['email'])->exists()) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Email already registered'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if (User::where('username', $data['username'])->exists()) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Username already taken'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Create user
            $user = User::createUser([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'display_name' => $data['display_name'] ?? $data['username']
            ]);

            $payload = json_encode([
                'success' => true,
                'message' => 'Account created successfully! You received 500 welcome eggs.',
                'data' => $user->toApiArray()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function login(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            if (!isset($data['email']) || !isset($data['password'])) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Email and password are required'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $user = User::where('email', $data['email'])->first();
            
            if (!$user || !password_verify($data['password'], $user->password_hash)) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            // Generate JWT token
            $config = require __DIR__ . '/../../config.php';
            $payload_jwt = [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'iat' => time(),
                'exp' => time() + $config['jwt']['expiration']
            ];

            $token = JWT::encode($payload_jwt, $config['jwt']['secret'], 'HS256');

            $payload = json_encode([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user->toApiArray(),
                    'token' => $token,
                    'expires_at' => date('Y-m-d H:i:s', $payload_jwt['exp'])
                ]
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function deleteAccount(Request $request, Response $response): Response
    {
        try {
            $userId = $this->getUserIdFromToken($request);
            
            $user = User::find($userId);
            if (!$user) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Delete related data first (due to foreign key constraints)
            FeatureRequest::where('user_id', $userId)->delete();
            FeatureVote::where('user_id', $userId)->delete();
            EggTransaction::where('user_id', $userId)->delete();
            
            // Delete the user
            $user->delete();

            $payload = json_encode([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    private function getUserIdFromToken(Request $request): int
    {
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            throw new \Exception('Authorization token required');
        }

        $token = $matches[1];
        $config = require __DIR__ . '/../../config.php';
        
        try {
            $decoded = JWT::decode($token, new Key($config['jwt']['secret'], 'HS256'));
            return $decoded->user_id;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token');
        }
    }
}