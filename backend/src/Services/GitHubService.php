<?php
declare(strict_types=1);

namespace App\Services;

class GitHubService
{
    private string $baseUrl = 'https://api.github.com';
    private ?string $token;
    private array $cache = [];

    public function __construct()
    {
        $this->token = $_ENV['GITHUB_TOKEN'] ?? null;
    }

    /**
     * Fetch issues from a repository with specific labels.
     * 
     * @param string $owner
     * @param string $repo
     * @param array $labels
     * @return array
     */
    public function getIssues(string $owner, string $repo, array $labels = []): array
    {
        $cacheKey = "issues_{$owner}_{$repo}_" . implode(',', $labels);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $query = [
            'state' => 'open',
            'per_page' => 50,
        ];
        
        if (!empty($labels)) {
            $query['labels'] = implode(',', $labels);
        }

        $url = "/repos/{$owner}/{$repo}/issues?" . http_build_query($query);
        $data = $this->request($url);
        
        // Filter out PRs if we only want issues (GitHub API returns both)
        $issues = array_filter($data, function($issue) {
            return !isset($issue['pull_request']);
        });

        $this->cache[$cacheKey] = array_values($issues);
        return $this->cache[$cacheKey];
    }

    /**
     * Parse quest data from issue labels and body.
     */
    public function parseQuest(array $issue): array
    {
        $quest = [
            'id' => $issue['id'],
            'number' => $issue['number'],
            'title' => $issue['title'],
            'url' => $issue['html_url'],
            'body' => $issue['body'],
            'difficulty' => 0,
            'class' => 'adventurer',
            'xp' => 0,
            'labels' => [],
        ];

        foreach ($issue['labels'] as $label) {
            $name = $label['name'];
            $quest['labels'][] = [
                'name' => $name,
                'color' => $label['color'],
            ];

            if (str_starts_with($name, 'difficulty:')) {
                $quest['difficulty'] = (int) substr($name, 11);
            }
            if (str_starts_with($name, 'class:')) {
                $quest['class'] = substr($name, 6);
            }
            if (str_starts_with($name, 'xp:')) {
                $quest['xp'] = (int) substr($name, 3);
            }
        }

        return $quest;
    }

    private function request(string $endpoint): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);
        $headers = [
            'User-Agent: WebHatchery-Frontpage',
            'Accept: application/vnd.github.v3+json',
        ];

        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            // Log error or return empty
            return [];
        }

        return json_decode($response, true) ?? [];
    }
}
