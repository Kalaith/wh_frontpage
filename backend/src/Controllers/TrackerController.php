<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\FeatureRequest;
use App\Models\ProjectSuggestion;
use App\Models\ActivityFeed;
use App\Models\Project;

class TrackerController
{
    /**
     * Get tracker dashboard stats
     */
    public function getStats(Request $request, Response $response): void
    {
        try {
            // Get project count
            $projectCount = Project::count();

            // Get feature request stats
            $featureStats = FeatureRequest::getStats();
            
            // Get suggestion stats  
            $suggestionStats = ProjectSuggestion::getStats();

            $stats = [
                'projects' => [
                    'total' => $projectCount
                ],
                'feature_requests' => $featureStats,
                'suggestions' => $suggestionStats
            ];

            $response->success($stats);

        } catch (\Exception $e) {
            $response->error('Error retrieving tracker stats: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get feature requests with optional filtering and sorting
     */
    public function getFeatureRequests(Request $request, Response $response): void
    {
        try {
            $filters = [
                'status' => $request->getParam('status'),
                'priority' => $request->getParam('priority'),
                'category' => $request->getParam('category'),
                'project_id' => $request->getParam('project_id')
            ];

            $sortBy = $request->getParam('sort_by', 'votes');
            $sortDirection = $request->getParam('sort_direction', 'desc');
            $limit = $request->getParam('limit');

            $requests = FeatureRequest::getByFilters(
                array_filter($filters), 
                (string)$sortBy, 
                (string)$sortDirection, 
                $limit ? (int)$limit : null
            );

            $response->success($requests);

        } catch (\Exception $e) {
            $response->error('Error retrieving feature requests: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new feature request
     */
    public function createFeatureRequest(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();

            // Validate required fields
            $required = ['title', 'description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->error("Field '{$field}' is required", 400);
                    return;
                }
            }

            // Process tags if provided
            if (isset($data['tags']) && is_string($data['tags'])) {
                $data['tags'] = array_map('trim', explode(',', $data['tags']));
            }

            // Create feature request
            $featureRequest = FeatureRequest::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'] ?? 'Enhancement',
                'priority' => $data['priority'] ?? 'Medium',
                'tags' => $data['tags'] ?? null,
                'project_id' => !empty($data['project_id']) ? (int)$data['project_id'] : null,
                'submitted_by' => $data['submitted_by'] ?? 'anonymous'
            ]);

            // Log activity
            ActivityFeed::logActivity(
                'feature_request',
                'created',
                'New feature request submitted',
                $featureRequest->title,
                $featureRequest->id,
                'feature_request',
                $featureRequest->submitted_by
            );

            $response->withStatus(201)->success($featureRequest->toApiArray(), 'Feature request created successfully');

        } catch (\Exception $e) {
            $response->error('Error creating feature request: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Get project suggestions with optional filtering and sorting
     */
    public function getProjectSuggestions(Request $request, Response $response): void
    {
        try {
            $filters = [
                'group' => $request->getParam('group'),
                'status' => $request->getParam('status')
            ];

            $sortBy = $request->getParam('sort_by', 'votes');
            $sortDirection = $request->getParam('sort_direction', 'desc');
            $limit = $request->getParam('limit');

            $suggestions = ProjectSuggestion::getByFilters(
                array_filter($filters), 
                (string)$sortBy, 
                (string)$sortDirection, 
                $limit ? (int)$limit : null
            );

            $response->success($suggestions);

        } catch (\Exception $e) {
            $response->error('Error retrieving project suggestions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Create a new project suggestion
     */
    public function createProjectSuggestion(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();

            // Validate required fields
            $required = ['name', 'description', 'rationale'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->error("Field '{$field}' is required", 400);
                    return;
                }
            }

            // Create project suggestion
            $suggestion = ProjectSuggestion::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'suggested_group' => $data['group'] ?? 'Web Applications',
                'rationale' => $data['rationale'],
                'submitted_by' => $data['submitted_by'] ?? 'anonymous'
            ]);

            // Log activity
            ActivityFeed::logActivity(
                'project_suggestion',
                'created',
                'New project suggested',
                $suggestion->name,
                $suggestion->id,
                'project_suggestion',
                $suggestion->submitted_by
            );

            $response->withStatus(201)->success($suggestion->toApiArray(), 'Project suggestion created successfully');

        } catch (\Exception $e) {
            $response->error('Error creating project suggestion: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Get recent activity feed
     */
    public function getActivityFeed(Request $request, Response $response): void
    {
        try {
            $limit = (int)$request->getParam('limit', 10);
            $projectId = $request->getParam('project_id');

            $activity = ActivityFeed::getRecentActivity($limit, $projectId ? (int)$projectId : null);

            $response->success($activity);

        } catch (\Exception $e) {
            $response->error('Error retrieving activity feed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Vote on an item (feature request or project suggestion)
     */
    public function vote(Request $request, Response $response): void
    {
        try {
            $data = $request->getBody();
            
            $itemType = $data['item_type'] ?? null; // 'feature_request' or 'project_suggestion'
            $itemId = $data['item_id'] ?? null;
            $voteValue = $data['vote_value'] ?? 1; // 1 for upvote, -1 for downvote

            if (!$itemType || !$itemId) {
                $response->error('item_type and item_id are required', 400);
                return;
            }

            if ($itemType === 'feature_request') {
                $item = FeatureRequest::find($itemId);
                if (!$item) {
                    $response->error('Feature request not found', 404);
                    return;
                }
                
                $item->votes += $voteValue;
                $item->save();
                
            } elseif ($itemType === 'project_suggestion') {
                $item = ProjectSuggestion::find($itemId);
                if (!$item) {
                    $response->error('Project suggestion not found', 404);
                    return;
                }
                
                $item->votes += $voteValue;
                $item->save();
            } else {
                $response->error('Invalid item_type', 400);
                return;
            }

            $response->success([
                'item_id' => $itemId,
                'item_type' => $itemType,
                'new_vote_count' => $item->votes
            ], 'Vote recorded successfully');

        } catch (\Exception $e) {
            $response->error('Error recording vote: ' . $e->getMessage(), 400);
        }
    }
}
?>