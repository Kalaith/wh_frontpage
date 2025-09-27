<?php

namespace App\Controllers;

use App\Services\ProjectUpdateService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProjectUpdateController
{
    private ProjectUpdateService $updateService;

    public function __construct()
    {
        $this->updateService = new ProjectUpdateService();
    }

    /**
     * Get all project updates with status information
     */
    public function getAllUpdates(Request $request, Response $response): Response
    {
        try {
            $projects = $this->updateService->getAllProjectUpdates();

            $data = [
                'success' => true,
                'data' => $projects,
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch project updates: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get project update statistics
     */
    public function getStatistics(Request $request, Response $response): Response
    {
        try {
            $stats = $this->updateService->getProjectStatistics();

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
                'error' => 'Failed to fetch project statistics: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get projects that need attention
     */
    public function getProjectsNeedingAttention(Request $request, Response $response): Response
    {
        try {
            $projects = $this->updateService->getProjectsNeedingAttention();

            $data = [
                'success' => true,
                'data' => $projects,
                'count' => count($projects),
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch projects needing attention: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get recent updates (last 7 days)
     */
    public function getRecentUpdates(Request $request, Response $response): Response
    {
        try {
            $allProjects = $this->updateService->getAllProjectUpdates();

            // Filter to only recent updates
            $recentProjects = array_filter($allProjects, function($project) {
                return $project['isRecent'] ?? false;
            });

            // Re-index array to ensure proper JSON encoding
            $recentProjects = array_values($recentProjects);

            $data = [
                'success' => true,
                'data' => $recentProjects,
                'count' => count($recentProjects),
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch recent updates: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}