<?php

namespace App\Controllers;

use App\Services\ProjectNewsFeedService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProjectNewsFeedController
{
    private ProjectNewsFeedService $newsFeedService;

    public function __construct()
    {
        $this->newsFeedService = new ProjectNewsFeedService();
    }

    /**
     * Get the main news feed
     */
    public function getNewsFeed(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            $limit = max(1, min(100, $limit)); // Clamp between 1-100

            $newsFeed = $this->newsFeedService->getNewsFeed($limit);

            $data = [
                'success' => true,
                'data' => $newsFeed,
                'count' => count($newsFeed),
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch news feed: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get recent activity (last 7 days by default)
     */
    public function getRecentActivity(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $days = isset($params['days']) ? (int)$params['days'] : 7;
            $days = max(1, min(30, $days)); // Clamp between 1-30 days

            $activity = $this->newsFeedService->getRecentActivity($days);

            $data = [
                'success' => true,
                'data' => $activity,
                'count' => count($activity),
                'days' => $days,
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch recent activity: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get changelog for a specific project
     */
    public function getProjectChangelog(Request $request, Response $response, array $args): Response
    {
        try {
            $projectName = $args['project'] ?? '';
            if (empty($projectName)) {
                throw new \InvalidArgumentException('Project name is required');
            }

            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $limit = max(1, min(50, $limit));

            $changelog = $this->newsFeedService->getProjectChangelog($projectName, $limit);

            $data = [
                'success' => true,
                'data' => $changelog,
                'project' => $projectName,
                'count' => count($changelog),
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch project changelog: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(Request $request, Response $response): Response
    {
        try {
            $stats = $this->newsFeedService->getActivityStats();

            $data = [
                'success' => true,
                'data' => $stats,
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch activity statistics: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}