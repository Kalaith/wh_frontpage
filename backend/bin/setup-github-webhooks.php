<?php
/**
 * Bulk Add GitHub Webhooks to All Project Repositories
 * 
 * Usage: php bin/setup-github-webhooks.php
 * 
 * Required .env variables:
 *   GITHUB_TOKEN=your_personal_access_token (needs admin:repo_hook scope)
 *   GITHUB_WEBHOOK_SECRET=your_webhook_secret
 *   APP_URL=https://yoursite.com (public URL where webhook can reach your API)
 */

declare(strict_types=1);

// Bootstrap
$autoloader = null;
$searchPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

foreach ($searchPaths as $path) {
    if (file_exists($path)) {
        $autoloader = $path;
        break;
    }
}

if (!$autoloader) {
    die("❌ Autoloader not found.\n");
}

require_once $autoloader;

use Dotenv\Dotenv;

try {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (\Throwable $e) {
    die("❌ Failed to load .env: " . $e->getMessage() . "\n");
}

// Configuration
$githubToken = $_ENV['GITHUB_TOKEN'] ?? null;
$webhookSecret = $_ENV['GITHUB_WEBHOOK_SECRET'] ?? null;
$appUrl = $_ENV['APP_URL'] ?? null;

if (!$githubToken) {
    die("❌ GITHUB_TOKEN not set in .env\n   Create one at: https://github.com/settings/tokens\n   Required scope: admin:repo_hook\n");
}

if (!$webhookSecret) {
    die("❌ GITHUB_WEBHOOK_SECRET not set in .env\n");
}

if (!$appUrl) {
    die("❌ APP_URL not set in .env (e.g., https://yoursite.com)\n");
}

$webhookUrl = rtrim($appUrl, '/') . '/api/webhooks/github';

echo "🔧 GitHub Webhook Setup Script\n";
echo "================================\n";
echo "Webhook URL: $webhookUrl\n\n";

// Database connection
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$db = $_ENV['DB_DATABASE'] ?? 'frontpage';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';
$port = $_ENV['DB_PORT'] ?? '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Get all projects with GitHub repository URLs
$stmt = $pdo->query("SELECT id, title, repository_url FROM projects WHERE repository_url IS NOT NULL AND repository_url LIKE '%github.com%'");
$projects = $stmt->fetchAll();

echo "Found " . count($projects) . " projects with GitHub repos\n\n";

$successCount = 0;
$skipCount = 0;
$errorCount = 0;

foreach ($projects as $project) {
    $repoUrl = $project['repository_url'];
    
    // Extract owner/repo from URL
    // Handles: https://github.com/owner/repo or https://github.com/owner/repo.git
    if (preg_match('#github\.com[:/]([^/]+)/([^/\.]+)#', $repoUrl, $matches)) {
        $owner = $matches[1];
        $repo = $matches[2];
    } else {
        echo "⚠️  {$project['title']}: Could not parse repo URL: $repoUrl\n";
        $errorCount++;
        continue;
    }

    echo "📦 {$project['title']} ($owner/$repo)... ";

    // Check if webhook already exists
    $existingWebhooks = githubApiRequest("GET", "/repos/$owner/$repo/hooks", $githubToken);
    
    if ($existingWebhooks === null) {
        echo "❌ Failed to fetch webhooks (check token permissions)\n";
        $errorCount++;
        continue;
    }

    $webhookExists = false;
    foreach ($existingWebhooks as $hook) {
        if (isset($hook['config']['url']) && $hook['config']['url'] === $webhookUrl) {
            $webhookExists = true;
            break;
        }
    }

    if ($webhookExists) {
        echo "⏭️  Already configured\n";
        $skipCount++;
        continue;
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

    $result = githubApiRequest("POST", "/repos/$owner/$repo/hooks", $githubToken, $payload);

    if ($result && isset($result['id'])) {
        echo "✅ Created webhook (ID: {$result['id']})\n";
        $successCount++;
    } else {
        echo "❌ Failed to create webhook\n";
        $errorCount++;
    }

    // Small delay to avoid rate limiting
    usleep(250000);
}

echo "\n================================\n";
echo "✅ Created: $successCount\n";
echo "⏭️  Skipped: $skipCount\n";
echo "❌ Errors: $errorCount\n";

function githubApiRequest(string $method, string $endpoint, string $token, ?array $data = null): ?array
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
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true) ?? [];
    }

    // Handle specific errors
    if ($httpCode === 404) {
        return null; // Repo not found or no access
    }
    if ($httpCode === 422) {
        // Webhook might already exist with different config
        return null;
    }

    return null;
}
