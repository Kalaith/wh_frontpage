<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\FeatureRequest;
use App\Models\User;
use App\Models\FeatureVote;
use App\Models\EggTransaction;
use App\Models\Project;
use App\Actions\GetAllFeaturesAction;
use App\Actions\CreateFeatureAction;
use Exception;

class FeatureRequestController
{
    public function __construct(
        private readonly GetAllFeaturesAction $getAllFeaturesAction,
        private readonly CreateFeatureAction $createFeatureAction
    ) {}

    public function getAllFeatures(Request $request, Response $response): void
    {
        try {
            $filters = [
                'status' => $request->getParam('status', 'approved'),
                'project_id' => $request->getParam('project_id'),
                'category' => $request->getParam('category'),
                'search' => $request->getParam('search')
            ];

            $sortBy = (string)$request->getParam('sort_by', 'total_eggs');
            $sortDirection = (string)$request->getParam('sort_direction', 'desc');
            $limit = (int)$request->getParam('limit', 50);

            $features = $this->getAllFeaturesAction->execute($filters, $sortBy, $sortDirection, $limit);

            $response->success($features);

        } catch (Exception $e) {
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

        } catch (Exception $e) {
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

            $result = $this->createFeatureAction->execute($data);

            $response->withStatus(201)->success($result, 'Feature request created successfully. It will be reviewed by administrators.');

        } catch (Exception $e) {
            $response->error('Failed to create feature request: ' . $e->getMessage(), (int)($e->getCode() ?: 500));
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
                (int)$data['user_id'], 
                (int)$data['feature_id'], 
                (int)$data['eggs_allocated']
            );

            if ($result['success']) {
                $response->success($result);
            } else {
                $response->error($result['message'] ?? 'Vote failed', 400);
            }

        } catch (Exception $e) {
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

        } catch (Exception $e) {
            $response->error('Failed to fetch user feature requests: ' . $e->getMessage(), 500);
        }
    }

    public function getUserVotes(Request $request, Response $response): void
    {
        try {
            $userId = (int)$request->getParam('user_id');
            
            $votes = FeatureVote::getUserVotes($userId);

            $response->success($votes);

        } catch (Exception $e) {
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

        } catch (Exception $e) {
            $response->error('Failed to fetch statistics: ' . $e->getMessage(), 500);
        }
    }
}
