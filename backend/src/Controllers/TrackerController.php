<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\FeatureRequest;
use App\Models\ProjectSuggestion;
use App\Models\ActivityFeed;
use App\Models\Project;
use App\Actions\GetTrackerStatsAction;
use App\Actions\GetFeatureRequestsAction;
use App\Actions\CreateFeatureRequestAction;
use Exception;

class TrackerController
{
    public function __construct(
        private readonly GetTrackerStatsAction $getTrackerStatsAction,
        private readonly GetFeatureRequestsAction $getFeatureRequestsAction,
        private readonly CreateFeatureRequestAction $createFeatureRequestAction,
        private readonly \App\Repositories\ProjectSuggestionCommentRepository $commentRepo,
        private readonly \App\Repositories\ProjectSuggestionRepository $suggestionRepo,
        private readonly \App\Repositories\ProjectRepository $projectRepo,
        private readonly \App\Repositories\ActivityFeedRepository $activityRepo,
        private readonly \App\Repositories\EggTransactionRepository $eggRepo
    ) {}

    /**
     * Get tracker dashboard stats
     */
    public function getStats(Request $request, Response $response): void
    {
        try {
            $stats = $this->getTrackerStatsAction->execute();
            $response->success($stats);
        } catch (Exception $e) {
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

            $sortBy = (string)$request->getParam('sort_by', 'votes');
            $sortDirection = (string)$request->getParam('sort_direction', 'desc');
            $limit = $request->getParam('limit');

            $requests = $this->getFeatureRequestsAction->execute(
                $filters, 
                $sortBy, 
                $sortDirection, 
                $limit ? (int)$limit : null
            );

            $response->success($requests);

        } catch (Exception $e) {
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

            $result = $this->createFeatureRequestAction->execute($data);

            $response->withStatus(201)->success($result, 'Feature request created successfully');

        } catch (Exception $e) {
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

            $sortBy = (string)$request->getParam('sort_by', 'votes');
            $sortDirection = (string)$request->getParam('sort_direction', 'desc');
            $limit = $request->getParam('limit');

            $suggestions = $this->suggestionRepo->getByFilters(
                array_filter($filters), 
                $sortBy, 
                $sortDirection, 
                $limit ? (int)$limit : null
            );

            $response->success($suggestions);

        } catch (Exception $e) {
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
            $userRole = $request->getAttribute('user_role', 'user');
            $userId = $data['user_id'] ?? $request->getAttribute('user_id');

            // Validate required fields
            $required = ['name', 'description', 'rationale'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->error("Field '{$field}' is required", 400);
                    return;
                }
            }

            // Egg Cost Logic
            $eggCost = 50;
            if ($userRole !== 'admin') {
                if (!$userId) {
                    $response->error('You must be logged in to submit a suggestion', 401);
                    return;
                }
                
                $balance = $this->eggRepo->getBalanceForUser((int)$userId);
                if ($balance < $eggCost) {
                    $response->error("Insufficient eggs. You need {$eggCost} eggs to submit a suggestion.", 402);
                    return;
                }
            }

            // Create project suggestion
            $suggestionId = $this->suggestionRepo->create([
                'name' => $data['name'],
                'description' => $data['description'],
                'tags' => $data['group'] ?? 'Web Applications',
                'rationale' => $data['rationale'],
                'status' => 'Suggested',
                'user_id' => $userId
            ]);
            
            // Deduct Eggs if not admin
            if ($userRole !== 'admin') {
                $this->eggRepo->create([
                    'user_id' => $userId,
                    'amount' => -$eggCost,
                    'type' => 'spent',
                    'description' => 'Project Suggestion Fee',
                    'reference_id' => $suggestionId,
                    'reference_type' => 'project_suggestion'
                ]);
            }
            
            // I need to fetch the suggestion back to return it
            $suggestion = $this->suggestionRepo->find($suggestionId);

            // Log activity
            $this->activityRepo->create([
                'user_id' => $userId,
                'activity_type' => 'project_suggestion',
                'message' => 'New project suggested: ' . $suggestion->name,
                'reference_id' => $suggestion->id,
                'reference_type' => 'project_suggestion',
                'metadata' => [
                    'action' => 'created',
                    'user_name' => $suggestion->submitted_by
                ]
            ]);

            $response->withStatus(201)->success($suggestion->toArray(), 'Project suggestion created successfully');

        } catch (Exception $e) {
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

            $activity = $this->activityRepo->all((int)$limit);

            $response->success($activity);

        } catch (Exception $e) {
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
            
            $itemType = (string)($data['item_type'] ?? ''); // 'feature_request' or 'project_suggestion'
            $itemId = $data['item_id'] ?? null;
            $voteValue = (int)($data['vote_value'] ?? 1); // 1 for upvote, -1 for downvote

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
                $item = $this->suggestionRepo->find($itemId);
                if (!$item) {
                    $response->error('Project suggestion not found', 404);
                    return;
                }
                
                $item->votes += $voteValue;
                $this->suggestionRepo->update($itemId, ['votes' => $item->votes]);
            } else {
                $response->error('Invalid item_type', 400);
                return;
            }

            $response->success([
                'item_id' => $itemId,
                'item_type' => $itemType,
                'new_vote_count' => $item->votes
            ], 'Vote recorded successfully');

        } catch (Exception $e) {
            $response->error('Error recording vote: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Get comments for a project suggestion
     */
    public function getSuggestionComments(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $comments = $this->commentRepo->getBySuggestionId($id);
            
            // Convert to array for response
            $data = array_map(fn($c) => $c->toArray(), $comments);
            
            $response->success($data);
        } catch (Exception $e) {
            $response->error('Error retrieving comments: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add a comment to a project suggestion
     */
    public function addSuggestionComment(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $data = $request->getBody();
            
            if (empty($data['content'])) {
                $response->error('Comment content is required', 400);
                return;
            }

            $commentData = [
                'project_suggestion_id' => $id,
                'user_id' => $data['user_id'] ?? null,
                'user_name' => $data['user_name'] ?? 'Anonymous',
                'content' => $data['content']
            ];

            $comment = $this->commentRepo->create($commentData);

            $response->withStatus(201)->success($comment->toArray(), 'Comment added successfully');
        } catch (Exception $e) {
            $response->error('Error adding comment: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Delete a project suggestion
     */
    public function deleteProjectSuggestion(Request $request, Response $response): void
    {
        try {
            // Require admin access
            $userRole = $request->getAttribute('user_role', 'user');
            if (strtolower((string)$userRole) !== 'admin') {
                $response->error('Admin access required', 403);
                return;
            }

            $id = (int)$request->getParam('id');

            // 1. Delete comments first (Manual Cascade)
            $this->commentRepo->deleteBySuggestionId($id);

            // 2. Delete the suggestion
            $count = $this->suggestionRepo->delete($id);

            // Log activity (Try to get name if possible, else generic)
            $this->activityRepo->create([
                'user_id' => $request->getAttribute('user_id'),
                'activity_type' => 'project_suggestion',
                'message' => 'Project suggestion deleted (ID: ' . $id . ')',
                'reference_id' => $id,
                'reference_type' => 'project_suggestion_deleted',
                'metadata' => ['action' => 'deleted', 'rows_affected' => $count]
            ]);

            $response->success([], 'Suggestion deleted successfully');

        } catch (Exception $e) {
            $response->error('Error deleting suggestion: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Publish a suggestion (promotes it to a real project)
     */
    public function publishSuggestion(Request $request, Response $response): void
    {
        try {
            // Require admin access
            $userRole = $request->getAttribute('user_role', 'user');
            if (strtolower((string)$userRole) !== 'admin') {
                $response->error('Admin access required', 403);
                return;
            }

            $id = (int)$request->getParam('id');
            $suggestion = $this->suggestionRepo->find($id);

            if (!$suggestion) {
                $response->error('Suggestion not found', 404);
                return;
            }

            // 1. Update suggestion status
            $this->suggestionRepo->update($id, ['status' => 'Published']);

            // 2. Create the project
            // Convert 'Web Applications' etc to 'apps' group codes
            $groupMap = [
                'Fiction Projects' => 'fiction',
                'Web Applications' => 'apps',
                'Games & Game Design' => 'games',
                'Game Design' => 'game_design'
            ];
            $groupName = $groupMap[$suggestion->suggested_group] ?? 'other';

            $projectData = [
                'title' => $suggestion->name,
                'description' => $suggestion->description,
                'stage' => 'Concept',
                'status' => 'planning', 
                'group_name' => $groupName,
                'version' => '0.1.0',
                'hidden' => true // Start hidden until configured
            ];
            
            $projectId = $this->projectRepo->create($projectData);

            $response->success([
                'suggestion' => $suggestion->toArray(),
                'new_project_id' => $projectId,
                'message' => 'Suggestion published and project created.'
            ], 'Suggestion published');

        } catch (Exception $e) {
            $response->error('Error publishing suggestion: ' . $e->getMessage(), 500);
        }
    }
}
