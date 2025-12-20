<?php

declare(strict_types=1);

namespace App\Services;

class ProjectNewsFeedService
{
    public function __construct(
        private readonly ProjectUpdateService $updateService
    ) {}

    /**
     * Generate news feed from recent project updates
     */
    public function getNewsFeed(int $limit = 20): array
    {
        $projects = $this->updateService->getAllProjectUpdates();

        $newsItems = [];

        foreach ($projects as $project) {
            // Generate news items from project updates
            $items = $this->generateNewsItemsForProject($project);
            $newsItems = array_merge($newsItems, $items);
        }

        // Sort by timestamp descending
        usort($newsItems, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return array_slice($newsItems, 0, $limit);
    }

    /**
     * Get recent activity feed (last 7 days)
     */
    public function getRecentActivity(int $days = 7): array
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->sub(new \DateInterval("P{$days}D"));
        $cutoffTimestamp = $cutoffDate->format('Y-m-d\TH:i:s\Z');

        $allNews = $this->getNewsFeed(100);

        return array_filter($allNews, function($item) use ($cutoffTimestamp) {
            return $item['timestamp'] >= $cutoffTimestamp;
        });
    }

    /**
     * Get changelog for a specific project
     */
    public function getProjectChangelog(string $projectName, int $limit = 10): array
    {
        $allNews = $this->getNewsFeed(200);

        $projectNews = array_filter($allNews, function($item) use ($projectName) {
            return $item['projectName'] === $projectName;
        });

        return array_slice($projectNews, 0, $limit);
    }

    /**
     * Generate news items for a project based on its manifest data
     */
    private function generateNewsItemsForProject(array $project): array
    {
        $items = [];
        $projectName = $project['name'] ?? $project['deployedName'] ?? 'Unknown';

        // News item for recent git updates
        if (isset($project['lastUpdated']) && $project['isRecent']) {
            $items[] = $this->createNewsItem(
                'code_update',
                $projectName,
                $this->generateUpdateMessage($project),
                $project['lastUpdated'],
                $project
            );
        }

        // News item for new deployments
        if (isset($project['lastBuild']) && $this->isRecentTimestamp($project['lastBuild'])) {
            $deploymentType = ($project['deploymentStatus'] === 'production') ? 'production' : 'development';

            $items[] = $this->createNewsItem(
                'deployment',
                $projectName,
                $this->generateDeploymentMessage($project, $deploymentType),
                $project['lastBuild'],
                $project
            );
        }

        // News item for status changes
        if ($project['updateUrgency'] === 'today') {
            $items[] = $this->createNewsItem(
                'status_change',
                $projectName,
                $this->generateStatusMessage($project),
                $project['lastUpdated'] ?? date('c'),
                $project
            );
        }

        return $items;
    }

    /**
     * Create a standardized news item
     */
    private function createNewsItem(string $type, string $projectName, string $message, string $timestamp, array $projectData): array
    {
        return [
            'id' => md5($type . $projectName . $timestamp . $message),
            'type' => $type,
            'projectName' => $projectName,
            'message' => $message,
            'timestamp' => $timestamp,
            'projectType' => $projectData['type'] ?? 'unknown',
            'deploymentStatus' => $projectData['deploymentStatus'] ?? 'unknown',
            'urgency' => $projectData['updateUrgency'] ?? 'unknown',
            'metadata' => [
                'gitCommit' => $projectData['gitCommit'] ?? null,
                'branch' => $projectData['branch'] ?? null,
                'environments' => $projectData['environments'] ?? []
            ]
        ];
    }

    /**
     * Generate a user-friendly update message from git data
     */
    private function generateUpdateMessage(array $project): string
    {
        $commitMessage = $project['lastCommitMessage'] ?? '';
        $branch = $project['branch'] ?? 'main';

        if ($commitMessage) {
            // Clean up commit message
            $cleanMessage = $this->cleanCommitMessage($commitMessage);
            return "Updated with: {$cleanMessage}";
        }

        return "New updates pushed to {$branch} branch";
    }

    /**
     * Generate deployment message
     */
    private function generateDeploymentMessage(array $project, string $deploymentType): string
    {
        $projectType = $project['type'] ?? 'project';
        $projectTypeDisplay = str_replace('_', ' ', $projectType);

        if ($deploymentType === 'production') {
            return "🚀 Deployed to production - {$projectTypeDisplay} is now live!";
        } else {
            return "🔨 Deployed to development environment for testing";
        }
    }

    /**
     * Generate status change message
     */
    private function generateStatusMessage(array $project): string
    {
        $urgency = $project['updateUrgency'] ?? 'unknown';
        $status = $project['deploymentStatus'] ?? 'unknown';

        if ($urgency === 'today' && $status === 'development_only') {
            return "⚡ Fresh updates available - not yet deployed to production";
        }

        if ($urgency === 'today' && $status === 'production') {
            return "✨ Latest changes are now live in production";
        }

        return "📊 Project status updated";
    }

    /**
     * Clean and format commit messages for display
     */
    private function cleanCommitMessage(string $message): string
    {
        // Remove common prefixes
        $message = preg_replace('/^(feat|fix|docs|style|refactor|test|chore):\s*/i', '', $message);

        // Remove automated commit suffixes
        $message = preg_replace('/\s*\[skip ci\]\s*$/i', '', $message);
        $message = preg_replace('/\s*🤖 Generated with.*$/i', '', $message);

        // Limit length and clean up
        $message = trim($message);
        if (strlen($message) > 100) {
            $message = substr($message, 0, 97) . '...';
        }

        return $message ?: 'Code updates';
    }

    /**
     * Check if timestamp is recent (within last 24 hours)
     */
    private function isRecentTimestamp(string $timestamp): bool
    {
        try {
            $date = new \DateTime($timestamp);
            $now = new \DateTime();
            $diff = $now->diff($date);

            return $diff->days === 0 && $diff->h < 24;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(): array
    {
        $recentActivity = $this->getRecentActivity(7);
        $allActivity = $this->getNewsFeed(200);

        $stats = [
            'total_activity_items' => count($allActivity),
            'recent_activity_items' => count($recentActivity),
            'activity_by_type' => [],
            'activity_by_project' => [],
            'most_active_projects' => []
        ];

        // Count by type
        foreach ($recentActivity as $item) {
            $type = $item['type'];
            if (!isset($stats['activity_by_type'][$type])) {
                $stats['activity_by_type'][$type] = 0;
            }
            $stats['activity_by_type'][$type]++;
        }

        // Count by project
        foreach ($recentActivity as $item) {
            $project = $item['projectName'];
            if (!isset($stats['activity_by_project'][$project])) {
                $stats['activity_by_project'][$project] = 0;
            }
            $stats['activity_by_project'][$project]++;
        }

        // Most active projects
        arsort($stats['activity_by_project']);
        $stats['most_active_projects'] = array_slice($stats['activity_by_project'], 0, 5, true);

        return $stats;
    }
}