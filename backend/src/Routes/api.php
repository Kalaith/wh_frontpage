<?php

use App\Controllers\ProjectController;
use App\Controllers\ProjectUpdateController;
use App\Controllers\ProjectNewsFeedController;
use App\Controllers\ProjectHealthController;
use App\Controllers\TrackerController;
use App\Controllers\FeatureRequestController;
use App\Controllers\UserController;
use App\Controllers\AdminController;
use App\Controllers\AuthProxyController;
use App\Controllers\GitHubWebhookController;
use App\Middleware\JwtAuthMiddleware;
use App\Models\Project;
use App\Core\Request;
use App\Core\Response;

// API Routes
// Note: $router is provided by the index.php that includes this file

// Public Health Check
$router->get('/api/health', function (Request $request, Response $response) {
    try {
        $response->success([
            'message' => 'Frontpage API is running',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'base_path' => $_ENV['APP_BASE_PATH'] ?? 'not set'
        ]);
    } catch (\Exception $e) {
        $response->error('Health check failed: ' . $e->getMessage(), 500);
    }
});

// Debug endpoint (Development only)
$router->get('/api/debug', function (Request $request, Response $response) {
    if (($_ENV['APP_ENV'] ?? 'production') !== 'development') {
        $response->error('Debug endpoint only available in development', 403);
        return;
    }

    $response->success([
        'environment' => $_ENV['APP_ENV'] ?? 'unknown',
        'base_path' => $_ENV['APP_BASE_PATH'] ?? 'not set',
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'server_info' => [
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown'
        ]
    ]);
});

// Public Projects Routes
$router->get('/api/projects', [ProjectController::class, 'getProjects']);
$router->get('/api/projects/homepage', [ProjectController::class, 'getHomepageProjects']);
$router->get('/api/projects/{group}', [ProjectController::class, 'getProjectsByGroup']);

// Public Project Update Routes
$router->get('/api/projects/updates', [ProjectUpdateController::class, 'getAllUpdates']);
$router->get('/api/projects/updates/recent', [ProjectUpdateController::class, 'getRecentUpdates']);
$router->get('/api/projects/updates/stats', [ProjectUpdateController::class, 'getStatistics']);
$router->get('/api/projects/updates/attention', [ProjectUpdateController::class, 'getProjectsNeedingAttention']);

// Public Quest Routes (Gamification)
$router->get('/api/quests', [\App\Controllers\QuestController::class, 'index']);

// Public Leaderboard Routes
$router->get('/api/leaderboard', [\App\Controllers\LeaderboardController::class, 'index']);

// Public Adventurer Profile Route
$router->get('/api/adventurers/{username}', [\App\Controllers\AdventurerController::class, 'show']);

// Public Boss Routes
$router->get('/api/bosses/current', [\App\Controllers\BossController::class, 'current']);

// Public Loot Crate Routes
$router->get('/api/crates/preview', [\App\Controllers\LootCrateController::class, 'preview']);
$router->get('/api/adventurers/{username}/crates', [\App\Controllers\LootCrateController::class, 'index']);
$router->post('/api/crates/{id}/open', [\App\Controllers\LootCrateController::class, 'open']);

// Public Quest Chain Routes
$router->get('/api/quest-chains', [\App\Controllers\QuestChainController::class, 'index']);
$router->get('/api/quest-chains/{slug}', [\App\Controllers\QuestChainController::class, 'show']);

// Public Wanderer Routes
$router->get('/api/adventurers/{username}/wanderer', [\App\Controllers\WandererController::class, 'stats']);

// Public News Feed Routes
$router->get('/api/news', [ProjectNewsFeedController::class, 'getNewsFeed']);
$router->get('/api/news/recent', [ProjectNewsFeedController::class, 'getRecentActivity']);
$router->get('/api/news/stats', [ProjectNewsFeedController::class, 'getActivityStats']);
$router->get('/api/news/project/{project}', [ProjectNewsFeedController::class, 'getProjectChangelog']);

// Public Health Monitoring Routes
$router->get('/api/health/system', [ProjectHealthController::class, 'getSystemHealth']);
$router->get('/api/health/summary', [ProjectHealthController::class, 'getHealthSummary']);
$router->get('/api/health/critical', [ProjectHealthController::class, 'getCriticalProjects']);
$router->get('/api/health/recommendations', [ProjectHealthController::class, 'getRecommendations']);
$router->get('/api/health/project/{project}', [ProjectHealthController::class, 'getProjectHealth']);
$router->post('/api/health/check', [ProjectHealthController::class, 'runHealthCheck']);

// Public Feature Request Routes
$router->get('/api/features', [FeatureRequestController::class, 'getAllFeatures']);
$router->get('/api/features/stats', [FeatureRequestController::class, 'getStats']);
$router->get('/api/features/{id}', [FeatureRequestController::class, 'getFeatureById']);

// Public Tracker Routes (Legacy)
$router->get('/api/tracker/stats', [TrackerController::class, 'getStats']);
$router->get('/api/tracker/feature-requests', [TrackerController::class, 'getFeatureRequests']);
$router->get('/api/tracker/project-suggestions', [TrackerController::class, 'getProjectSuggestions']);
$router->get('/api/tracker/activity', [TrackerController::class, 'getActivityFeed']);
$router->post('/api/tracker/feature-requests', [TrackerController::class, 'createFeatureRequest']);
$router->post('/api/tracker/project-suggestions', [TrackerController::class, 'createProjectSuggestion']);
$router->get('/api/tracker/project-suggestions/{id}/comments', [TrackerController::class, 'getSuggestionComments']);
$router->post('/api/tracker/project-suggestions/{id}/comments', [TrackerController::class, 'addSuggestionComment']);
$router->post('/api/tracker/project-suggestions/{id}/publish', [TrackerController::class, 'publishSuggestion'], [JwtAuthMiddleware::class]);
$router->delete('/api/tracker/project-suggestions/{id}', [TrackerController::class, 'deleteProjectSuggestion'], [JwtAuthMiddleware::class]);
$router->post('/api/tracker/vote', [TrackerController::class, 'vote']);

// Auth endpoints
$router->post('/api/auth/login', [UserController::class, 'login']);
$router->post('/api/auth/register', [UserController::class, 'register']);

// Proxy auth endpoints (backup)
$router->post('/api/auth/proxy/login', [AuthProxyController::class, 'login']);
$router->post('/api/auth/proxy/register', [AuthProxyController::class, 'register']);
$router->get('/api/auth/proxy/user', [AuthProxyController::class, 'getCurrentUser']);

// Temporary (UNSECURED) admin endpoint to initialize the database
$router->post('/api/admin/init-db', function (Request $request, Response $response) {
    try {
        Project::createTable();
        $response->success(null, 'Database initialized (projects table created or already exists)');
    } catch (\Exception $e) {
        $response->error('Initialization failed: ' . $e->getMessage(), 500);
    }
});

// Protected project management routes
$router->post('/api/projects', [ProjectController::class, 'createProject'], [JwtAuthMiddleware::class]);
$router->put('/api/projects/{id}', [ProjectController::class, 'updateProject'], [JwtAuthMiddleware::class]);
$router->delete('/api/projects/{id}', [ProjectController::class, 'deleteProject'], [JwtAuthMiddleware::class]);

// Protected feature request routes
$router->post('/api/features', [FeatureRequestController::class, 'createFeature'], [JwtAuthMiddleware::class]);
$router->post('/api/features/vote', [FeatureRequestController::class, 'voteOnFeature'], [JwtAuthMiddleware::class]);
$router->get('/api/users/{user_id}/features', [FeatureRequestController::class, 'getUserFeatures'], [JwtAuthMiddleware::class]);
$router->get('/api/users/{user_id}/votes', [FeatureRequestController::class, 'getUserVotes'], [JwtAuthMiddleware::class]);

// User routes
$router->get('/api/user/profile', [UserController::class, 'getProfile'], [JwtAuthMiddleware::class]);
$router->put('/api/user/profile', [UserController::class, 'updateProfile'], [JwtAuthMiddleware::class]);
$router->post('/api/user/claim-daily-eggs', [UserController::class, 'claimDailyEggs'], [JwtAuthMiddleware::class]);
$router->get('/api/user/transactions', [UserController::class, 'getTransactions'], [JwtAuthMiddleware::class]);
$router->get('/api/user/dashboard', [UserController::class, 'getDashboard'], [JwtAuthMiddleware::class]);
$router->delete('/api/user/delete-account', [UserController::class, 'deleteAccount'], [JwtAuthMiddleware::class]);

// Admin routes
$router->get('/api/admin/features/pending', [AdminController::class, 'getPendingFeatures'], [JwtAuthMiddleware::class]);
$router->post('/api/admin/features/{id}/approve', [AdminController::class, 'approveFeature'], [JwtAuthMiddleware::class]);
$router->post('/api/admin/features/{id}/reject', [AdminController::class, 'rejectFeature'], [JwtAuthMiddleware::class]);
$router->put('/api/admin/features/{id}/status', [AdminController::class, 'updateFeatureStatus'], [JwtAuthMiddleware::class]);
$router->post('/api/admin/features/bulk-approve', [AdminController::class, 'bulkApproveFeatures'], [JwtAuthMiddleware::class]);
$router->post('/api/admin/users/{id}/eggs', [AdminController::class, 'adjustUserEggs'], [JwtAuthMiddleware::class]);
$router->get('/api/admin/stats', [AdminController::class, 'getAdminStats'], [JwtAuthMiddleware::class]);
$router->get('/api/admin/users', [AdminController::class, 'getUserManagement'], [JwtAuthMiddleware::class]);

// Webhook Routes (no auth - verified by signature)
$router->post('/api/webhooks/github', [GitHubWebhookController::class, 'handlePush']);

// Admin webhook setup (IP restricted via ALLOWED_ADMIN_IP in .env)
$router->get('/api/admin/setup-webhooks', [GitHubWebhookController::class, 'setupWebhooks']);
$router->post('/api/admin/setup-webhooks', [GitHubWebhookController::class, 'setupWebhooks']);

// Mark project as deployed (called by publish.ps1)
$router->get('/api/admin/mark-deployed', [GitHubWebhookController::class, 'markDeployed']);
$router->post('/api/admin/mark-deployed', [GitHubWebhookController::class, 'markDeployed']);

// Public migration endpoint (Temporary)
$router->get('/api/migrate', [\App\Controllers\MigrationController::class, 'runPublicMigration']);

// Admin sync/migrations endpoint (IP restricted)
$router->get('/api/admin/run-sync', [\App\Controllers\MigrationController::class, 'runSync']);
$router->post('/api/admin/run-sync', [\App\Controllers\MigrationController::class, 'runSync']);
