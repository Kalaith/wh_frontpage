<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ProjectHealthService;

class ProjectHealthController
{
    private ProjectHealthService $healthService;

    public function __construct()
    {
        $this->healthService = new ProjectHealthService();
    }

    /**
     * Get comprehensive system health report
     */
    public function getSystemHealth(Request $request, Response $response): void
    {
        try {
            $health = $this->healthService->getSystemHealth();

            $response->success($health);

        } catch (\Exception $e) {
            $response->error('Failed to fetch system health: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get health summary for dashboard display
     */
    public function getHealthSummary(Request $request, Response $response): void
    {
        try {
            $summary = $this->healthService->getHealthSummary();

            $response->success($summary);

        } catch (\Exception $e) {
            $response->error('Failed to fetch health summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get health status for a specific project
     */
    public function getProjectHealth(Request $request, Response $response): void
    {
        try {
            $projectName = $request->getParam('project', '');
            if (empty($projectName)) {
                $response->error('Project name is required', 400);
                return;
            }

            // Get all projects and find the specific one
            $systemHealth = $this->healthService->getSystemHealth();
            $projectHealth = null;

            foreach ($systemHealth['project_health'] as $project) {
                if ($project['project_name'] === $projectName) {
                    $projectHealth = $project;
                    break;
                }
            }

            if ($projectHealth === null) {
                $response->error('Project not found', 404);
                return;
            }

            $response->success($projectHealth);

        } catch (\Exception $e) {
            $response->error('Failed to fetch project health: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get projects with critical issues
     */
    public function getCriticalProjects(Request $request, Response $response): void
    {
        try {
            $systemHealth = $this->healthService->getSystemHealth();

            // Filter for projects with critical status
            $criticalProjects = array_filter($systemHealth['project_health'], function($project) {
                return $project['status'] === 'critical';
            });

            // Re-index array for proper JSON encoding
            $criticalProjects = array_values($criticalProjects);

            $response->success($criticalProjects);

        } catch (\Exception $e) {
            $response->error('Failed to fetch critical projects: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get system recommendations
     */
    public function getRecommendations(Request $request, Response $response): void
    {
        try {
            $systemHealth = $this->healthService->getSystemHealth();

            $response->success($systemHealth['recommendations']);

        } catch (\Exception $e) {
            $response->error('Failed to fetch recommendations: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Run health check on demand and return fresh results
     */
    public function runHealthCheck(Request $request, Response $response): void
    {
        try {
            // This forces a fresh health check rather than using any cached data
            $health = $this->healthService->getSystemHealth();

            $response->success($health, 'Health check completed');

        } catch (\Exception $e) {
            $response->error('Health check failed: ' . $e->getMessage(), 500);
        }
    }
}
