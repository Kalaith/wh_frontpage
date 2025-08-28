<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\FeatureVote;
use App\Models\EggTransaction;
use App\Models\Project;

class FeatureRequestController
{
    public function getAllFeatures(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            
            $status = $queryParams['status'] ?? 'approved';
            $project_id = $queryParams['project_id'] ?? null;
            $category = $queryParams['category'] ?? null;
            $sort_by = $queryParams['sort_by'] ?? 'total_eggs';
            $sort_direction = $queryParams['sort_direction'] ?? 'desc';
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50;
            $search = $queryParams['search'] ?? null;

            $query = FeatureRequest::query();

            // Apply filters
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            if ($project_id) {
                $query->where('project_id', $project_id);
            }

            if ($category) {
                $query->where('category', $category);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $query->orderBy($sort_by, $sort_direction);

            if ($limit > 0) {
                $query->limit($limit);
            }

            // Load relationships
            $query->with(['user:id,username,display_name', 'project:id,title']);

            $features = $query->get()->map(function ($feature) {
                return $feature->toApiArray();
            });

            $payload = json_encode([
                'success' => true,
                'data' => $features,
                'count' => $features->count()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to fetch feature requests',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getFeatureById(Request $request, Response $response, array $args): Response
    {
        try {
            $featureId = (int)$args['id'];
            
            $feature = FeatureRequest::with(['user:id,username,display_name', 'project:id,title'])
                ->find($featureId);

            if (!$feature) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Feature request not found'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Get votes for this feature
            $votes = FeatureVote::getFeatureVotes($featureId);

            $data = $feature->toApiArray();
            $data['votes'] = $votes;

            $payload = json_encode([
                'success' => true,
                'data' => $data
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to fetch feature request',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createFeature(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate required fields
            $required = ['title', 'description', 'user_id'];
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

            // Verify user exists and has enough eggs
            $user = User::find($data['user_id']);
            if (!$user) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            if ($user->egg_balance < 100) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Insufficient eggs. Creating a feature request costs 100 eggs.'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            // Verify project exists if provided
            if (isset($data['project_id']) && $data['project_id']) {
                $project = Project::find($data['project_id']);
                if (!$project) {
                    $payload = json_encode([
                        'success' => false,
                        'message' => 'Project not found'
                    ]);
                    $response->getBody()->write($payload);
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            // Create feature request
            $feature = FeatureRequest::create([
                'user_id' => $data['user_id'],
                'project_id' => $data['project_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'] ?? null,
                'use_case' => $data['use_case'] ?? null,
                'expected_benefits' => $data['expected_benefits'] ?? null,
                'priority_level' => $data['priority_level'] ?? 'medium',
                'feature_type' => $data['feature_type'] ?? 'enhancement',
                'tags' => isset($data['tags']) ? json_encode($data['tags']) : null,
                'status' => 'pending'
            ]);

            // Deduct eggs from user
            $user->spendEggs(100, "Created feature request: {$feature->title}", $feature->id, 'feature_request');

            $payload = json_encode([
                'success' => true,
                'message' => 'Feature request created successfully. It will be reviewed by administrators.',
                'data' => $feature->toApiArray()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to create feature request',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function voteOnFeature(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            // Validate required fields
            $required = ['user_id', 'feature_id', 'eggs_allocated'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    $payload = json_encode([
                        'success' => false,
                        'message' => "Field '{$field}' is required"
                    ]);
                    $response->getBody()->write($payload);
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
                }
            }

            if ($data['eggs_allocated'] <= 0) {
                $payload = json_encode([
                    'success' => false,
                    'message' => 'Eggs allocated must be greater than 0'
                ]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $result = FeatureVote::castVote(
                $data['user_id'], 
                $data['feature_id'], 
                $data['eggs_allocated']
            );

            $status = $result['success'] ? 200 : 400;
            $payload = json_encode($result);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus($status);

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to cast vote',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getUserFeatures(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['user_id'];
            
            $features = FeatureRequest::where('user_id', $userId)
                ->with('project:id,title')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($feature) {
                    return $feature->toApiArray();
                });

            $payload = json_encode([
                'success' => true,
                'data' => $features
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to fetch user feature requests',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getUserVotes(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['user_id'];
            
            $votes = FeatureVote::getUserVotes($userId);

            $payload = json_encode([
                'success' => true,
                'data' => $votes
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to fetch user votes',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getStats(Request $request, Response $response): Response
    {
        try {
            $stats = [
                'total_features' => FeatureRequest::count(),
                'pending_approval' => FeatureRequest::where('status', 'pending')->count(),
                'approved_features' => FeatureRequest::where('status', 'approved')->count(),
                'in_progress' => FeatureRequest::where('status', 'in_progress')->count(),
                'completed_features' => FeatureRequest::where('status', 'completed')->count(),
                'total_eggs_allocated' => FeatureRequest::sum('total_eggs'),
                'total_votes' => FeatureVote::count(),
                'active_voters' => FeatureVote::distinct('user_id')->count(),
            ];

            $payload = json_encode([
                'success' => true,
                'data' => $stats
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}