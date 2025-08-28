<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\FeatureRequest;
use App\Models\ProjectSuggestion;
use App\Models\ActivityFeed;
use App\Models\Project;

class TrackerController
{
    /**
     * Get tracker dashboard stats
     */
    public function getStats(Request $request, Response $response): Response
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

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error retrieving tracker stats: ' . $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get feature requests with optional filtering and sorting
     */
    public function getFeatureRequests(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            
            $filters = [
                'status' => $params['status'] ?? null,
                'priority' => $params['priority'] ?? null,
                'category' => $params['category'] ?? null,
                'project_id' => $params['project_id'] ?? null
            ];

            $sortBy = $params['sort_by'] ?? 'votes';
            $sortDirection = $params['sort_direction'] ?? 'desc';
            $limit = isset($params['limit']) ? (int)$params['limit'] : null;

            $requests = FeatureRequest::getByFilters(
                array_filter($filters), 
                $sortBy, 
                $sortDirection, 
                $limit
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $requests
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error retrieving feature requests: ' . $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Create a new feature request
     */
    public function createFeatureRequest(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            // Validate required fields
            $required = ['title', 'description'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("Field '{$field}' is required");
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

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $featureRequest->toApiArray(),
                'message' => 'Feature request created successfully'
            ]));

            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error creating feature request: ' . $e->getMessage()
            ]));

            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get project suggestions with optional filtering and sorting
     */
    public function getProjectSuggestions(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            
            $filters = [
                'group' => $params['group'] ?? null,
                'status' => $params['status'] ?? null
            ];

            $sortBy = $params['sort_by'] ?? 'votes';
            $sortDirection = $params['sort_direction'] ?? 'desc';
            $limit = isset($params['limit']) ? (int)$params['limit'] : null;

            $suggestions = ProjectSuggestion::getByFilters(
                array_filter($filters), 
                $sortBy, 
                $sortDirection, 
                $limit
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $suggestions
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error retrieving project suggestions: ' . $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Create a new project suggestion
     */
    public function createProjectSuggestion(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            // Validate required fields
            $required = ['name', 'description', 'rationale'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \InvalidArgumentException("Field '{$field}' is required");
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

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $suggestion->toApiArray(),
                'message' => 'Project suggestion created successfully'
            ]));

            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error creating project suggestion: ' . $e->getMessage()
            ]));

            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Get recent activity feed
     */
    public function getActivityFeed(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $projectId = isset($params['project_id']) ? (int)$params['project_id'] : null;

            $activity = ActivityFeed::getRecentActivity($limit, $projectId);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $activity
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error retrieving activity feed: ' . $e->getMessage()
            ]));

            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Vote on an item (feature request or project suggestion)
     */
    public function vote(Request $request, Response $response): Response
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            
            $itemType = $data['item_type'] ?? null; // 'feature_request' or 'project_suggestion'
            $itemId = $data['item_id'] ?? null;
            $voteValue = $data['vote_value'] ?? 1; // 1 for upvote, -1 for downvote

            if (!$itemType || !$itemId) {
                throw new \InvalidArgumentException('item_type and item_id are required');
            }

            // Get voter identification (IP for anonymous voting)
            $voterIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
            
            // For now, we'll just increment/decrement the vote count directly
            // In a full implementation, you'd want to track individual votes to prevent duplicates
            
            if ($itemType === 'feature_request') {
                $item = FeatureRequest::find($itemId);
                if (!$item) {
                    throw new \Exception('Feature request not found');
                }
                
                $item->votes += $voteValue;
                $item->save();
                
            } elseif ($itemType === 'project_suggestion') {
                $item = ProjectSuggestion::find($itemId);
                if (!$item) {
                    throw new \Exception('Project suggestion not found');
                }
                
                $item->votes += $voteValue;
                $item->save();
            } else {
                throw new \InvalidArgumentException('Invalid item_type');
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'item_id' => $itemId,
                    'item_type' => $itemType,
                    'new_vote_count' => $item->votes
                ],
                'message' => 'Vote recorded successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Error recording vote: ' . $e->getMessage()
            ]));

            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
?>