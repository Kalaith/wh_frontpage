<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ProjectUpdateService;

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
    public function getAllUpdates(Request $request, Response $response): void
    {
        try {
            $projects = $this->updateService->getAllProjectUpdates();

            $response->success($projects);

        } catch (\Exception $e) {
            $response->error('Failed to fetch project updates: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get project update statistics
     */
    public function getStatistics(Request $request, Response $response): void
    {
        try {
            $stats = $this->updateService->getProjectStatistics();

            $response->success($stats);

        } catch (\Exception $e) {
            $response->error('Failed to fetch project statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get projects that need attention
     */
    public function getProjectsNeedingAttention(Request $request, Response $response): void
    {
        try {
            $projects = $this->updateService->getProjectsNeedingAttention();

            $response->success($projects);

        } catch (\Exception $e) {
            $response->error('Failed to fetch projects needing attention: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get recent updates (last 7 days)
     */
    public function getRecentUpdates(Request $request, Response $response): void
    {
        try {
            $allProjects = $this->updateService->getAllProjectUpdates();

            // Filter to only recent updates
            $recentProjects = array_filter($allProjects, function($project) {
                return $project['isRecent'] ?? false;
            });

            // Re-index array to ensure proper JSON encoding
            $recentProjects = array_values($recentProjects);

            $response->success($recentProjects);

        } catch (\Exception $e) {
            $response->error('Failed to fetch recent updates: ' . $e->getMessage(), 500);
        }
    }
}
