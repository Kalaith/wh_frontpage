<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\FeatureVote;
use App\Models\EggTransaction;
use App\Models\Project;

class FeatureRequestController
{
    public function getAllFeatures(Request $request, Response $response): void
    {
        try {
            $status = $request->getParam('status', 'approved');
            $project_id = $request->getParam('project_id');
            $category = $request->getParam('category');
            $sort_by = $request->getParam('sort_by', 'total_eggs');
            $sort_direction = $request->getParam('sort_direction', 'desc');
            $limit = $request->getParam('limit', 50);
            $search = $request->getParam('search');

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
            $query->orderBy((string)$sort_by, (string)$sort_direction);

            if ((int)$limit > 0) {
                $query->limit((int)$limit);
            }

            // Load relationships
            $query->with(['user:id,username,display_name', 'project:id,title']);

            $features = $query->get()->map(function ($feature) {
                return $feature->toApiArray();
            });

            $response->success($features);

        } catch (\Exception $e) {
            $response->error('Failed to fetch feature requests: ' . $e->getMessage(), 500);
        }
    }

    public function getFeatureById(Request $request, Response $response): void
    {
        try {
            $featureId = (int)$request->getParam('id');
            
            $feature = FeatureRequest::with(['user:id,username,display_name', 'project:id,title'])
                ->find($featureId);

            if (!$feature) {
                $response->error('Feature request not found', 404);
                return;
            }

            // Get votes for this feature
            $votes = FeatureVote::getFeatureVotes($featureId);

            $data = $feature->toApiArray();
            $data['votes'] = $votes;

            $response->success($data);

        } catch (\Exception $e) {
            $response->error('Failed to fetch feature request: ' . $e->getMessage(), 500);
        }
    }

    public function createFeature(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();
            
            // Validate required fields
            $required = ['title', 'description', 'user_id'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    $response->error("Field '{$field}' is required", 400);
                    return;
                }
            }

            // Verify user exists and has enough eggs
            $user = User::find($data['user_id']);
            if (!$user) {
                $response->error('User not found', 400);
                return;
            }

            if ($user->egg_balance < 100) {
                $response->error('Insufficient eggs. Creating a feature request costs 100 eggs.', 400);
                return;
            }

            // Verify project exists if provided
            if (isset($data['project_id']) && $data['project_id']) {
                $project = Project::find($data['project_id']);
                if (!$project) {
                    $response->error('Project not found', 400);
                    return;
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

            $response->withStatus(201)->success($feature->toApiArray(), 'Feature request created successfully. It will be reviewed by administrators.');

        } catch (\Exception $e) {
            $response->error('Failed to create feature request: ' . $e->getMessage(), 500);
        }
    }

    public function voteOnFeature(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();
            
            // Validate required fields
            $required = ['user_id', 'feature_id', 'eggs_allocated'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    $response->error("Field '{$field}' is required", 400);
                    return;
                }
            }

            if ($data['eggs_allocated'] <= 0) {
                $response->error('Eggs allocated must be greater than 0', 400);
                return;
            }

            $result = FeatureVote::castVote(
                $data['user_id'], 
                $data['feature_id'], 
                $data['eggs_allocated']
            );

            if ($result['success']) {
                $response->success($result);
            } else {
                $response->error($result['message'], 400);
            }

        } catch (\Exception $e) {
            $response->error('Failed to cast vote: ' . $e->getMessage(), 500);
        }
    }

    public function getUserFeatures(Request $request, Response $response): void
    {
        try {
            $userId = (int)$request->getParam('user_id');
            
            $features = FeatureRequest::where('user_id', $userId)
                ->with('project:id,title')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($feature) {
                    return $feature->toApiArray();
                });

            $response->success($features);

        } catch (\Exception $e) {
            $response->error('Failed to fetch user feature requests: ' . $e->getMessage(), 500);
        }
    }

    public function getUserVotes(Request $request, Response $response): void
    {
        try {
            $userId = (int)$request->getParam('user_id');
            
            $votes = FeatureVote::getUserVotes($userId);

            $response->success($votes);

        } catch (\Exception $e) {
            $response->error('Failed to fetch user votes: ' . $e->getMessage(), 500);
        }
    }

    public function getStats(Request $request, Response $response): void
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

            $response->success($stats);

        } catch (\Exception $e) {
            $response->error('Failed to fetch statistics: ' . $e->getMessage(), 500);
        }
    }
}
