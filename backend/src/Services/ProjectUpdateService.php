<?php

namespace App\Services;

class ProjectUpdateService
{
    private array $projectPaths;

    public function __construct()
    {
        // Scan deployment directories where projects are actually published
        $this->projectPaths = [
            'preview' => $_ENV['PREVIEW_ROOT'] ?? 'H:\\xampp\\htdocs',
            'production' => $_ENV['PRODUCTION_ROOT'] ?? 'F:\\WebHatchery'
        ];
    }

    /**
     * Scan all projects and aggregate their manifests
     */
    public function getAllProjectUpdates(): array
    {
        $projects = [];

        // Scan deployment directories (preview and production)
        foreach ($this->projectPaths as $environment => $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            // Scan all subdirectories in the deployment path
            $projectDirs = glob($basePath . '/*', GLOB_ONLYDIR);
            foreach ($projectDirs as $projectDir) {
                $projectName = basename($projectDir);

                // Skip backend directories and other non-project folders
                if (in_array($projectName, ['backend', 'vendor', 'storage', 'logs', 'tmp'])) {
                    continue;
                }

                $manifest = $this->readProjectManifest($projectDir);
                if ($manifest) {
                    // Determine project type from manifest or infer from name
                    $projectType = $manifest['type'] ?? $this->inferProjectType($projectName);

                    $manifest['type'] = $projectType;
                    $manifest['environment'] = $environment;
                    $manifest['path'] = $projectDir;
                    $manifest['deployedName'] = $projectName;

                    // Use a unique key combining name and environment to avoid duplicates
                    $uniqueKey = $projectName . '_' . $environment;
                    $projects[$uniqueKey] = $manifest;
                }
            }

            // Handle frontpage (root deployment) specially
            $frontpageManifest = $this->readProjectManifest($basePath);
            if ($frontpageManifest) {
                $frontpageManifest['type'] = 'frontpage';
                $frontpageManifest['environment'] = $environment;
                $frontpageManifest['path'] = $basePath;
                $frontpageManifest['deployedName'] = 'frontpage';

                $uniqueKey = 'frontpage_' . $environment;
                $projects[$uniqueKey] = $frontpageManifest;
            }
        }

        // Convert back to indexed array and merge duplicates by project name
        $mergedProjects = $this->mergeProjectEnvironments(array_values($projects));

        return $this->processProjectData($mergedProjects);
    }

    /**
     * Read and parse a project.json manifest file
     */
    private function readProjectManifest(string $projectPath): ?array
    {
        $manifestPath = $projectPath . '/project.json';

        if (!file_exists($manifestPath)) {
            return null;
        }

        try {
            $content = file_get_contents($manifestPath);
            $manifest = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error in {$manifestPath}: " . json_last_error_msg());
                return null;
            }

            return $manifest;
        } catch (Exception $e) {
            error_log("Error reading manifest {$manifestPath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Process project data to add computed fields and status information
     */
    private function processProjectData(array $projects): array
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        foreach ($projects as &$project) {
            // Calculate time since last update
            if (isset($project['lastUpdated'])) {
                try {
                    $lastUpdate = new \DateTime($project['lastUpdated']);
                    $project['daysSinceUpdate'] = $now->diff($lastUpdate)->days;
                    $project['isRecent'] = $project['daysSinceUpdate'] <= 7; // Recent if updated within 7 days
                } catch (Exception $e) {
                    $project['daysSinceUpdate'] = null;
                    $project['isRecent'] = false;
                }
            }

            // Calculate time since last build
            if (isset($project['lastBuild'])) {
                try {
                    $lastBuild = new \DateTime($project['lastBuild']);
                    $project['daysSinceBuild'] = $now->diff($lastBuild)->days;
                } catch (Exception $e) {
                    $project['daysSinceBuild'] = null;
                }
            }

            // Determine deployment status
            $project['deploymentStatus'] = $this->getDeploymentStatus($project);

            // Add update urgency level
            $project['updateUrgency'] = $this->calculateUpdateUrgency($project);
        }

        // Sort by most recently updated first
        usort($projects, function($a, $b) {
            $aTime = $a['lastUpdated'] ?? '1970-01-01T00:00:00Z';
            $bTime = $b['lastUpdated'] ?? '1970-01-01T00:00:00Z';
            return strcmp($bTime, $aTime);
        });

        return $projects;
    }

    /**
     * Determine deployment status based on environments and timestamps
     */
    private function getDeploymentStatus(array $project): string
    {
        $environments = $project['environments'] ?? [];
        $hasProduction = in_array('production', $environments);
        $hasPreview = in_array('preview', $environments);

        // Also check deployment timestamps for backward compatibility
        $hasProductionDeploy = isset($project['deployment']['production']);
        $hasPreviewDeploy = isset($project['deployment']['development']);

        if ($hasProduction || $hasProductionDeploy) {
            return 'production';
        }

        if ($hasPreview || $hasPreviewDeploy) {
            return 'development_only';
        }

        return 'not_deployed';
    }

    /**
     * Calculate update urgency based on various factors
     */
    private function calculateUpdateUrgency(array $project): string
    {
        // If no git information, can't determine urgency
        if (!isset($project['daysSinceUpdate'])) {
            return 'unknown';
        }

        $daysSinceUpdate = $project['daysSinceUpdate'];

        if ($daysSinceUpdate === 0) {
            return 'today';
        } elseif ($daysSinceUpdate <= 3) {
            return 'recent';
        } elseif ($daysSinceUpdate <= 14) {
            return 'moderate';
        } else {
            return 'stale';
        }
    }

    /**
     * Get summary statistics about all projects
     */
    public function getProjectStatistics(): array
    {
        $projects = $this->getAllProjectUpdates();

        $stats = [
            'total_projects' => count($projects),
            'recent_updates' => 0,
            'production_deployments' => 0,
            'development_only' => 0,
            'not_deployed' => 0,
            'by_type' => []
        ];

        foreach ($projects as $project) {
            // Count recent updates (last 7 days)
            if ($project['isRecent'] ?? false) {
                $stats['recent_updates']++;
            }

            // Count deployment statuses
            switch ($project['deploymentStatus']) {
                case 'production':
                    $stats['production_deployments']++;
                    break;
                case 'development_only':
                    $stats['development_only']++;
                    break;
                case 'not_deployed':
                    $stats['not_deployed']++;
                    break;
            }

            // Count by project type
            $type = $project['type'] ?? 'unknown';
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = 0;
            }
            $stats['by_type'][$type]++;
        }

        return $stats;
    }

    /**
     * Get projects that need attention (recent updates, deployment issues, etc.)
     */
    public function getProjectsNeedingAttention(): array
    {
        $projects = $this->getAllProjectUpdates();

        return array_filter($projects, function($project) {
            // Projects updated today or recently with no production deployment
            $recentlyUpdated = ($project['updateUrgency'] ?? '') === 'today';
            $noProductionDeploy = ($project['deploymentStatus'] ?? '') !== 'production';

            return $recentlyUpdated && $noProductionDeploy;
        });
    }

    /**
     * Infer project type from project name/path
     */
    private function inferProjectType(string $projectName): string
    {
        // Common game app patterns
        $gamePatterns = [
            'adventurer', 'guild', 'forge', 'blacksmith', 'daemon', 'ashes', 'chyrralon'
        ];

        foreach ($gamePatterns as $pattern) {
            if (stripos($projectName, $pattern) !== false) {
                return 'game_apps';
            }
        }

        // Common app patterns
        $appPatterns = [
            'studio', 'generator', 'tracker', 'auth', 'campaign', 'anime_prompt'
        ];

        foreach ($appPatterns as $pattern) {
            if (stripos($projectName, $pattern) !== false) {
                return 'apps';
            }
        }

        // Default to apps if uncertain
        return 'apps';
    }

    /**
     * Merge projects that exist in multiple environments
     */
    private function mergeProjectEnvironments(array $projects): array
    {
        $merged = [];

        foreach ($projects as $project) {
            $projectName = $project['deployedName'] ?? $project['name'] ?? 'unknown';

            if (!isset($merged[$projectName])) {
                $merged[$projectName] = $project;
                $merged[$projectName]['environments'] = [$project['environment']];
            } else {
                // Merge environment data
                if (!in_array($project['environment'], $merged[$projectName]['environments'])) {
                    $merged[$projectName]['environments'][] = $project['environment'];
                }

                // Use production data if available, otherwise preview
                if ($project['environment'] === 'production') {
                    $merged[$projectName] = array_merge($merged[$projectName], $project);
                    $merged[$projectName]['environments'] = array_unique(
                        array_merge($merged[$projectName]['environments'], [$project['environment']])
                    );
                }
            }
        }

        return array_values($merged);
    }
}