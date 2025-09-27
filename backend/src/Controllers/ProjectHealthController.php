<?php

namespace App\Controllers;

use App\Services\ProjectHealthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
    public function getSystemHealth(Request $request, Response $response): Response
    {
        try {
            $health = $this->healthService->getSystemHealth();

            $data = [
                'success' => true,
                'data' => $health,
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch system health: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get health summary for dashboard display
     */
    public function getHealthSummary(Request $request, Response $response): Response
    {
        try {
            $summary = $this->healthService->getHealthSummary();

            $data = [
                'success' => true,
                'data' => $summary,
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch health summary: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get health status for a specific project
     */
    public function getProjectHealth(Request $request, Response $response, array $args): Response
    {
        try {
            $projectName = $args['project'] ?? '';
            if (empty($projectName)) {
                throw new \InvalidArgumentException('Project name is required');
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
                throw new \InvalidArgumentException('Project not found');
            }

            $data = [
                'success' => true,
                'data' => $projectHealth,
                'project' => $projectName,
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch project health: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get projects with critical issues
     */
    public function getCriticalProjects(Request $request, Response $response): Response
    {
        try {
            $systemHealth = $this->healthService->getSystemHealth();

            // Filter for projects with critical status
            $criticalProjects = array_filter($systemHealth['project_health'], function($project) {
                return $project['status'] === 'critical';
            });

            // Re-index array for proper JSON encoding
            $criticalProjects = array_values($criticalProjects);

            $data = [
                'success' => true,
                'data' => $criticalProjects,
                'count' => count($criticalProjects),
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch critical projects: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Get system recommendations
     */
    public function getRecommendations(Request $request, Response $response): Response
    {
        try {
            $systemHealth = $this->healthService->getSystemHealth();

            $data = [
                'success' => true,
                'data' => $systemHealth['recommendations'],
                'count' => count($systemHealth['recommendations']),
                'timestamp' => date('c')
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Failed to fetch recommendations: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Run health check on demand and return fresh results
     */
    public function runHealthCheck(Request $request, Response $response): Response
    {
        try {
            // This forces a fresh health check rather than using any cached data
            $health = $this->healthService->getSystemHealth();

            $data = [
                'success' => true,
                'message' => 'Health check completed',
                'data' => $health,
                'scan_time' => date('c'),
                'projects_scanned' => $health['total_projects']
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => 'Health check failed: ' . $e->getMessage()
            ];

            $response->getBody()->write(json_encode($error));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}