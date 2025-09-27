<?php

namespace App\Services;

class ProjectHealthService
{
    private ProjectUpdateService $updateService;

    public function __construct()
    {
        $this->updateService = new ProjectUpdateService();
    }

    /**
     * Perform comprehensive health checks on all projects
     */
    public function getSystemHealth(): array
    {
        $projects = $this->updateService->getAllProjectUpdates();

        $health = [
            'overall_status' => 'healthy',
            'total_projects' => count($projects),
            'healthy_projects' => 0,
            'warning_projects' => 0,
            'critical_projects' => 0,
            'issues' => [],
            'recommendations' => [],
            'project_health' => []
        ];

        foreach ($projects as $project) {
            $projectHealth = $this->checkProjectHealth($project);
            $health['project_health'][] = $projectHealth;

            // Count by health status
            switch ($projectHealth['status']) {
                case 'healthy':
                    $health['healthy_projects']++;
                    break;
                case 'warning':
                    $health['warning_projects']++;
                    break;
                case 'critical':
                    $health['critical_projects']++;
                    break;
            }

            // Collect issues
            $health['issues'] = array_merge($health['issues'], $projectHealth['issues']);
        }

        // Determine overall system status
        $health['overall_status'] = $this->calculateOverallStatus($health);

        // Generate system-wide recommendations
        $health['recommendations'] = $this->generateSystemRecommendations($health);

        return $health;
    }

    /**
     * Check health of a specific project
     */
    public function checkProjectHealth(array $project): array
    {
        $projectName = $project['name'] ?? $project['deployedName'] ?? 'Unknown';

        $health = [
            'project_name' => $projectName,
            'status' => 'healthy',
            'score' => 100,
            'issues' => [],
            'checks' => [],
            'last_checked' => date('c')
        ];

        // Run various health checks
        $this->checkGitStatus($project, $health);
        $this->checkDeploymentStatus($project, $health);
        $this->checkUpdateFrequency($project, $health);
        $this->checkProjectFiles($project, $health);
        $this->checkEnvironmentSync($project, $health);

        // Calculate final status and score
        $health['status'] = $this->calculateProjectStatus($health);

        return $health;
    }

    /**
     * Check git-related health indicators
     */
    private function checkGitStatus(array $project, array &$health): void
    {
        $checks = [];

        // Check if git information is present
        if (empty($project['gitCommit'])) {
            $this->addIssue($health, 'warning', 'git_missing', 'No git commit information available');
            $checks[] = ['name' => 'Git Integration', 'status' => 'warning', 'message' => 'Git data missing'];
        } else {
            $checks[] = ['name' => 'Git Integration', 'status' => 'healthy', 'message' => 'Git tracking active'];
        }

        // Check commit message quality
        if (!empty($project['lastCommitMessage'])) {
            $message = $project['lastCommitMessage'];
            if (strlen($message) < 10) {
                $this->addIssue($health, 'info', 'commit_message', 'Commit message is very short');
                $checks[] = ['name' => 'Commit Quality', 'status' => 'info', 'message' => 'Short commit message'];
            } else {
                $checks[] = ['name' => 'Commit Quality', 'status' => 'healthy', 'message' => 'Good commit practices'];
            }
        }

        $health['checks'] = array_merge($health['checks'], $checks);
    }

    /**
     * Check deployment-related health
     */
    private function checkDeploymentStatus(array $project, array &$health): void
    {
        $checks = [];
        $deploymentStatus = $project['deploymentStatus'] ?? 'not_deployed';

        switch ($deploymentStatus) {
            case 'production':
                $checks[] = ['name' => 'Deployment', 'status' => 'healthy', 'message' => 'Deployed to production'];
                break;

            case 'development_only':
                $this->addIssue($health, 'warning', 'no_production', 'Project not deployed to production');
                $checks[] = ['name' => 'Deployment', 'status' => 'warning', 'message' => 'Development only'];
                break;

            case 'not_deployed':
                $this->addIssue($health, 'critical', 'not_deployed', 'Project not deployed anywhere');
                $checks[] = ['name' => 'Deployment', 'status' => 'critical', 'message' => 'Not deployed'];
                break;
        }

        // Check deployment freshness
        if (isset($project['lastBuild'])) {
            $daysSinceBuild = $project['daysSinceBuild'] ?? 0;

            if ($daysSinceBuild > 30) {
                $this->addIssue($health, 'warning', 'stale_build', "Last build was {$daysSinceBuild} days ago");
                $checks[] = ['name' => 'Build Freshness', 'status' => 'warning', 'message' => 'Build is getting old'];
            } else {
                $checks[] = ['name' => 'Build Freshness', 'status' => 'healthy', 'message' => 'Recent build'];
            }
        }

        $health['checks'] = array_merge($health['checks'], $checks);
    }

    /**
     * Check update frequency and staleness
     */
    private function checkUpdateFrequency(array $project, array &$health): void
    {
        $checks = [];
        $urgency = $project['updateUrgency'] ?? 'unknown';

        switch ($urgency) {
            case 'today':
            case 'recent':
                $checks[] = ['name' => 'Update Frequency', 'status' => 'healthy', 'message' => 'Recently updated'];
                break;

            case 'moderate':
                $checks[] = ['name' => 'Update Frequency', 'status' => 'info', 'message' => 'Moderate update activity'];
                break;

            case 'stale':
                $this->addIssue($health, 'warning', 'stale_project', 'Project has not been updated recently');
                $checks[] = ['name' => 'Update Frequency', 'status' => 'warning', 'message' => 'Infrequent updates'];
                break;

            default:
                $this->addIssue($health, 'info', 'unknown_activity', 'Cannot determine update activity');
                $checks[] = ['name' => 'Update Frequency', 'status' => 'info', 'message' => 'Activity unknown'];
        }

        $health['checks'] = array_merge($health['checks'], $checks);
    }

    /**
     * Check for essential project files and structure
     */
    private function checkProjectFiles(array $project, array &$health): void
    {
        $checks = [];
        $projectPath = $project['path'] ?? '';

        if (empty($projectPath) || !is_dir($projectPath)) {
            $this->addIssue($health, 'critical', 'missing_files', 'Project directory not found');
            $checks[] = ['name' => 'Project Files', 'status' => 'critical', 'message' => 'Directory missing'];
            $health['checks'] = array_merge($health['checks'], $checks);
            return;
        }

        $essentialFiles = ['index.html', 'index.php'];
        $hasEssentialFile = false;

        foreach ($essentialFiles as $file) {
            if (file_exists($projectPath . '/' . $file)) {
                $hasEssentialFile = true;
                break;
            }
        }

        if (!$hasEssentialFile) {
            $this->addIssue($health, 'warning', 'no_entry_point', 'No main entry point file found');
            $checks[] = ['name' => 'Project Files', 'status' => 'warning', 'message' => 'Missing entry point'];
        } else {
            $checks[] = ['name' => 'Project Files', 'status' => 'healthy', 'message' => 'Essential files present'];
        }

        $health['checks'] = array_merge($health['checks'], $checks);
    }

    /**
     * Check environment synchronization
     */
    private function checkEnvironmentSync(array $project, array &$health): void
    {
        $checks = [];
        $environments = $project['environments'] ?? [];

        if (count($environments) > 1) {
            $checks[] = ['name' => 'Environment Sync', 'status' => 'healthy', 'message' => 'Multi-environment deployment'];
        } elseif (count($environments) === 1) {
            $env = $environments[0];
            if ($env === 'production') {
                $checks[] = ['name' => 'Environment Sync', 'status' => 'healthy', 'message' => 'Production deployment'];
            } else {
                $this->addIssue($health, 'info', 'single_env', 'Only deployed to preview environment');
                $checks[] = ['name' => 'Environment Sync', 'status' => 'info', 'message' => 'Single environment'];
            }
        } else {
            $this->addIssue($health, 'warning', 'no_env', 'No environment information available');
            $checks[] = ['name' => 'Environment Sync', 'status' => 'warning', 'message' => 'Environment data missing'];
        }

        $health['checks'] = array_merge($health['checks'], $checks);
    }

    /**
     * Add an issue to the health report
     */
    private function addIssue(array &$health, string $severity, string $code, string $message): void
    {
        $health['issues'][] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'project' => $health['project_name'] ?? 'Unknown'
        ];

        // Adjust health score based on severity
        switch ($severity) {
            case 'critical':
                $health['score'] -= 25;
                break;
            case 'warning':
                $health['score'] -= 10;
                break;
            case 'info':
                $health['score'] -= 2;
                break;
        }

        $health['score'] = max(0, $health['score']);
    }

    /**
     * Calculate project status based on issues
     */
    private function calculateProjectStatus(array $health): string
    {
        $criticalIssues = 0;
        $warningIssues = 0;

        foreach ($health['issues'] as $issue) {
            switch ($issue['severity']) {
                case 'critical':
                    $criticalIssues++;
                    break;
                case 'warning':
                    $warningIssues++;
                    break;
            }
        }

        if ($criticalIssues > 0) {
            return 'critical';
        }

        if ($warningIssues > 0) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Calculate overall system status
     */
    private function calculateOverallStatus(array $health): string
    {
        $totalProjects = $health['total_projects'];
        $criticalProjects = $health['critical_projects'];
        $warningProjects = $health['warning_projects'];

        if ($totalProjects === 0) {
            return 'unknown';
        }

        $criticalPercentage = ($criticalProjects / $totalProjects) * 100;
        $warningPercentage = ($warningProjects / $totalProjects) * 100;

        if ($criticalPercentage > 20) {
            return 'critical';
        }

        if ($criticalPercentage > 0 || $warningPercentage > 50) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Generate system-wide recommendations
     */
    private function generateSystemRecommendations(array $health): array
    {
        $recommendations = [];

        if ($health['critical_projects'] > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'action' => 'Address critical project issues immediately',
                'details' => "You have {$health['critical_projects']} project(s) with critical issues that need immediate attention."
            ];
        }

        if ($health['warning_projects'] > $health['healthy_projects']) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => 'Review project deployment pipeline',
                'details' => 'More projects have warnings than are healthy. Consider reviewing deployment processes.'
            ];
        }

        // Analyze common issues
        $issueTypes = [];
        foreach ($health['issues'] as $issue) {
            $issueTypes[$issue['code']] = ($issueTypes[$issue['code']] ?? 0) + 1;
        }

        arsort($issueTypes);
        $topIssue = array_key_first($issueTypes);

        if ($topIssue && $issueTypes[$topIssue] > 1) {
            $recommendations[] = [
                'priority' => 'medium',
                'action' => "Address common issue: {$topIssue}",
                'details' => "This issue affects {$issueTypes[$topIssue]} projects and should be addressed systematically."
            ];
        }

        return $recommendations;
    }

    /**
     * Get health summary for dashboard
     */
    public function getHealthSummary(): array
    {
        $fullHealth = $this->getSystemHealth();

        return [
            'overall_status' => $fullHealth['overall_status'],
            'total_projects' => $fullHealth['total_projects'],
            'healthy_projects' => $fullHealth['healthy_projects'],
            'warning_projects' => $fullHealth['warning_projects'],
            'critical_projects' => $fullHealth['critical_projects'],
            'top_issues' => array_slice($fullHealth['issues'], 0, 5),
            'urgent_recommendations' => array_filter($fullHealth['recommendations'], function($rec) {
                return $rec['priority'] === 'high';
            })
        ];
    }
}