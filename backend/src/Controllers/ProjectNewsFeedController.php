<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ProjectNewsFeedService;

class ProjectNewsFeedController
{
    public function __construct(
        private readonly ProjectNewsFeedService $newsFeedService
    ) {}

    /**
     * Get the main news feed
     */
    public function getNewsFeed(Request $request, Response $response): void
    {
        try {
            $limit = (int)$request->getParam('limit', 20);
            $limit = max(1, min(100, $limit)); // Clamp between 1-100

            $newsFeed = $this->newsFeedService->getNewsFeed($limit);

            $response->success($newsFeed);

        } catch (\Exception $e) {
            $response->error('Failed to fetch news feed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get recent activity (last 7 days by default)
     */
    public function getRecentActivity(Request $request, Response $response): void
    {
        try {
            $days = (int)$request->getParam('days', 7);
            $days = max(1, min(30, $days)); // Clamp between 1-30 days

            $activity = $this->newsFeedService->getRecentActivity($days);

            $response->success($activity);

        } catch (\Exception $e) {
            $response->error('Failed to fetch recent activity: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get changelog for a specific project
     */
    public function getProjectChangelog(Request $request, Response $response): void
    {
        try {
            $projectName = $request->getParam('project', '');
            if (empty($projectName)) {
                $response->error('Project name is required', 400);
                return;
            }

            $limit = (int)$request->getParam('limit', 10);
            $limit = max(1, min(50, $limit));

            $changelog = $this->newsFeedService->getProjectChangelog($projectName, $limit);

            $response->success($changelog);

        } catch (\Exception $e) {
            $response->error('Failed to fetch project changelog: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(Request $request, Response $response): void
    {
        try {
            $stats = $this->newsFeedService->getActivityStats();

            $response->success($stats);

        } catch (\Exception $e) {
            $response->error('Failed to fetch activity statistics: ' . $e->getMessage(), 500);
        }
    }
}
