<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\ProjectRepository;
use App\Repositories\ProjectGitRepository;

final class GitHubWebhookController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectGitRepository $projectGitRepository
    ) {}

    /**
     * Handle incoming GitHub webhook push events
     */
    public function handlePush(Request $request, Response $response): void
    {
        // Verify signature if secret is configured
        $secret = $_ENV['GITHUB_WEBHOOK_SECRET'] ?? throw new \RuntimeException('GITHUB_WEBHOOK_SECRET environment variable is not set');
        if ($secret) {
            $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
            $payload = file_get_contents('php://input');
            
            if (!$this->verifySignature($payload, $signature, $secret)) {
                $response->withStatus(401)->json(['error' => 'Invalid signature']);
                return;
            }
        }

        $data = $request->getBody();
        
        // Only handle push events
        $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown';
        if ($event !== 'push') {
            $response->success(['message' => "Ignored event: $event"]);
            return;
        }

        // Extract repo info
        $repoName = $data['repository']['name'] ?? null;
        $repoFullName = $data['repository']['full_name'] ?? null;
        $repoUrl = $data['repository']['html_url'] ?? null;
        
        if (!$repoName) {
            $response->error('Missing repository name', 400);
            return;
        }

        // Extract git metadata from push event
        $ref = $data['ref'] ?? '';
        $branch = str_replace('refs/heads/', '', $ref);
        
        $headCommit = $data['head_commit'] ?? [];
        $commitHash = $headCommit['id'] ?? ($data['after'] ?? null);
        $commitMessage = $headCommit['message'] ?? null;
        $timestamp = $headCommit['timestamp'] ?? date('Y-m-d H:i:s');

        // Find project by repo URL or name
        $project = $this->findProjectByRepo($repoUrl, $repoName, $repoFullName);
        
        if (!$project) {
            // Log but don't error - the repo might not be tracked
            $response->success([
                'message' => "No matching project found for repo: $repoName",
                'searched' => ['url' => $repoUrl, 'name' => $repoName]
            ]);
            return;
        }

        // Update projects_git table
        $gitData = [
            'last_updated' => date('Y-m-d H:i:s', strtotime($timestamp)),
            'branch' => $branch,
            'git_commit' => $commitHash ? substr($commitHash, 0, 40) : null,
            'last_commit_message' => $commitMessage ? substr($commitMessage, 0, 500) : null,
        ];

        $this->projectGitRepository->upsert((int)$project['id'], $gitData);

        $response->success([
            'message' => 'Git metadata updated',
            'project' => $project['title'],
            'branch' => $branch,
            'commit' => $commitHash ? substr($commitHash, 0, 7) : null
        ]);
    }

    /**
     * Verify GitHub webhook signature
     */
    private function verifySignature(string $payload, string $signature, string $secret): bool
    {
        if (empty($signature)) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Find project by repository URL or name
     */
    private function findProjectByRepo(?string $url, string $name, ?string $fullName): ?array
    {
        // Try by exact URL match first
        if ($url) {
            $projects = $this->projectRepository->all();
            foreach ($projects as $project) {
                if ($project['repository_url'] === $url) {
                    return $project;
                }
                // Also check without .git suffix
                if ($project['repository_url'] === $url . '.git') {
                    return $project;
                }
                if (rtrim($project['repository_url'], '.git') === rtrim($url, '.git')) {
                    return $project;
                }
            }
        }

        // Try by title matching repo name
        $project = $this->projectRepository->findByTitle($name);
        if ($project) {
            return $project;
        }

        // Try by path containing repo name
        $project = $this->projectRepository->findByPathLike($name);
        if ($project) {
            return $project;
        }

        return null;
    }

    /**
     * Bulk setup webhooks on all GitHub repos (admin endpoint)
     * Requires GITHUB_TOKEN, APP_URL, and ALLOWED_ADMIN_IP in .env
     */
    public function setupWebhooks(Request $request, Response $response): void
    {
        // IP restriction for security
        $allowedIp = $_ENV['ALLOWED_ADMIN_IP'] ?? throw new \RuntimeException('ALLOWED_ADMIN_IP environment variable is not set');
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $clientIp = explode(',', $clientIp)[0]; // Handle proxy chains
        
        if ($allowedIp && trim($clientIp) !== trim($allowedIp)) {
            $response->withStatus(403)->json(['error' => 'Access denied']);
            return;
        }

        $githubToken = $_ENV['GITHUB_TOKEN'] ?? throw new \RuntimeException('GITHUB_TOKEN environment variable is not set');
        $webhookSecret = $_ENV['GITHUB_WEBHOOK_SECRET'] ?? throw new \RuntimeException('GITHUB_WEBHOOK_SECRET environment variable is not set');
        $appUrl = $_ENV['APP_URL'] ?? throw new \RuntimeException('APP_URL environment variable is not set');

        if (!$githubToken) {
            $response->error('GITHUB_TOKEN not configured in .env', 500);
            return;
        }

        if (!$webhookSecret) {
            $response->error('GITHUB_WEBHOOK_SECRET not configured in .env', 500);
            return;
        }

        if (!$appUrl) {
            $response->error('APP_URL not configured in .env', 500);
            return;
        }

        $webhookUrl = rtrim($appUrl, '/') . '/api/webhooks/github';

        // Get all projects with GitHub URLs
        $projects = $this->projectRepository->all();
        $githubProjects = array_filter($projects, function($p) {
            return !empty($p['repository_url']) && strpos($p['repository_url'], 'github.com') !== false;
        });

        $results = [
            'webhook_url' => $webhookUrl,
            'total_projects' => count($githubProjects),
            'summary' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
            'details' => [
                'created' => [],
                'skipped' => [],
                'errors' => []
            ]
        ];

        foreach ($githubProjects as $project) {
            $repoUrl = $project['repository_url'];
            $projectInfo = ['title' => $project['title'], 'repo' => $repoUrl];
            
            // Extract owner/repo from URL
            if (!preg_match('#github\.com[:/]([^/]+)/([^/\.]+)#', $repoUrl, $matches)) {
                $projectInfo['reason'] = 'Could not parse repo URL';
                $results['details']['errors'][] = $projectInfo;
                $results['summary']['errors']++;
                continue;
            }

            $owner = $matches[1];
            $repo = $matches[2];
            $projectInfo['github'] = "$owner/$repo";

            // Check if webhook already exists
            $existingWebhooks = $this->githubApiRequest("GET", "/repos/$owner/$repo/hooks", $githubToken);
            
            if ($existingWebhooks === null) {
                $projectInfo['reason'] = 'Failed to fetch webhooks (check token permissions)';
                $results['details']['errors'][] = $projectInfo;
                $results['summary']['errors']++;
                continue;
            }

            // Delete any existing webhooks pointing to our domain
            $appDomain = parse_url($appUrl, PHP_URL_HOST);
            $deletedIds = [];
            foreach ($existingWebhooks as $hook) {
                $hookUrl = $hook['config']['url'] ?? '';
                $hookDomain = parse_url($hookUrl, PHP_URL_HOST);
                
                // If this webhook points to our domain, delete it
                if ($hookDomain && $hookDomain === $appDomain && isset($hook['id'])) {
                    $deleteResult = $this->githubApiRequest("DELETE", "/repos/$owner/$repo/hooks/{$hook['id']}", $githubToken);
                    $deletedIds[] = $hook['id'];
                }
            }
            
            if (!empty($deletedIds)) {
                $projectInfo['deleted_webhooks'] = $deletedIds;
            }

            // Create webhook
            $payload = [
                'name' => 'web',
                'active' => true,
                'events' => ['push'],
                'config' => [
                    'url' => $webhookUrl,
                    'content_type' => 'json',
                    'secret' => $webhookSecret,
                    'insecure_ssl' => '0'
                ]
            ];

            $result = $this->githubApiRequest("POST", "/repos/$owner/$repo/hooks", $githubToken, $payload);

            if ($result && isset($result['id'])) {
                $projectInfo['webhook_id'] = $result['id'];
                $results['details']['created'][] = $projectInfo;
                $results['summary']['created']++;
            } else {
                $projectInfo['reason'] = 'Failed to create webhook';
                $results['details']['errors'][] = $projectInfo;
                $results['summary']['errors']++;
            }

            // Small delay to avoid rate limiting
            usleep(100000);
        }

        $response->success($results, "Webhook setup complete: {$results['summary']['created']} created, {$results['summary']['skipped']} already configured, {$results['summary']['errors']} errors");
    }

    /**
     * Make a request to GitHub API
     */
    private function githubApiRequest(string $method, string $endpoint, string $token, ?array $data = null): ?array
    {
        $url = "https://api.github.com" . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Accept: application/vnd.github+json",
            "X-GitHub-Api-Version: 2022-11-28",
            "User-Agent: WebHatchery-Webhook-Setup"
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true) ?? [];
        }

        return null;
    }

    /**
     * Mark a project as deployed to an environment
     * Called by publish.ps1 after successful deployment
     */
    public function markDeployed(Request $request, Response $response): void
    {
        // IP restriction for security
        $allowedIp = $_ENV['ALLOWED_ADMIN_IP'] ?? throw new \RuntimeException('ALLOWED_ADMIN_IP environment variable is not set');
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $clientIp = explode(',', $clientIp)[0];
        
        if ($allowedIp && trim($clientIp) !== trim($allowedIp)) {
            $response->withStatus(403)->json(['error' => 'Access denied']);
            return;
        }

        // Accept both POST body and GET query params
        $data = $request->getBody();
        $projectName = $data['project'] ?? $_GET['project'] ?? null;
        $environment = $data['environment'] ?? $_GET['environment'] ?? 'production';

        if (!$projectName) {
            $response->error('Missing project name', 400);
            return;
        }

        try {
            // Find project by title or path
            $project = $this->projectRepository->findByTitle($projectName);
            if (!$project) {
                $project = $this->projectRepository->findByPathLike($projectName);
            }

            if (!$project) {
                $response->error("Project not found: $projectName", 404);
                return;
            }

            // Add environment to project's environments
            $this->projectGitRepository->addEnvironment((int)$project['id'], $environment);

            $response->success([
                'project' => $project['title'],
                'environment' => $environment,
                'message' => "Marked as deployed to $environment"
            ]);

        } catch (\Exception $e) {
            $response->error('Failed to mark deployed: ' . $e->getMessage(), 500);
        }
    }
}
