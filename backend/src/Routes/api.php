<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ProjectController;
use App\Controllers\ProjectUpdateController;
use App\Controllers\ProjectNewsFeedController;
use App\Controllers\ProjectHealthController;
use App\Controllers\TrackerController;
use App\Controllers\FeatureRequestController;
use App\Controllers\UserController;
use App\Controllers\AdminController;
use App\Middleware\JwtAuthMiddleware;
use App\Models\Project;

// API Routes
$app->group('/api', function (RouteCollectorProxy $group) {
    // Public Health Check (No Auth Required)
    $group->get('/health', function ($request, $response) {
        try {
            $payload = json_encode([
                'success' => true,
                'message' => 'Frontpage API is running',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'environment' => $_ENV['APP_ENV'] ?? 'unknown',
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'base_path' => $_ENV['APP_BASE_PATH'] ?? 'not set'
            ]);
            
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $payload = json_encode([
                'success' => false,
                'message' => 'Health check failed',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    });

    // Debug endpoint (Development only)
    $group->get('/debug', function ($request, $response) {
        if ($_ENV['APP_ENV'] !== 'development') {
            $payload = json_encode(['success' => false, 'message' => 'Debug endpoint only available in development']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        $debugInfo = [
            'success' => true,
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
            'base_path' => $_ENV['APP_BASE_PATH'] ?? 'not set',
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'server_info' => [
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'unknown'
            ]
        ];

        $payload = json_encode($debugInfo, JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Public Projects Routes (No Auth Required)
    $group->get('/projects', [ProjectController::class, 'getProjects']);
    $group->get('/projects/homepage', [ProjectController::class, 'getHomepageProjects']);
    $group->get('/projects/{group}', [ProjectController::class, 'getProjectsByGroup']);

    // Public Project Update Routes (No Auth Required)
    $group->get('/projects/updates', [ProjectUpdateController::class, 'getAllUpdates']);
    $group->get('/projects/updates/recent', [ProjectUpdateController::class, 'getRecentUpdates']);
    $group->get('/projects/updates/stats', [ProjectUpdateController::class, 'getStatistics']);
    $group->get('/projects/updates/attention', [ProjectUpdateController::class, 'getProjectsNeedingAttention']);

    // Public News Feed Routes (No Auth Required)
    $group->get('/news', [ProjectNewsFeedController::class, 'getNewsFeed']);
    $group->get('/news/recent', [ProjectNewsFeedController::class, 'getRecentActivity']);
    $group->get('/news/stats', [ProjectNewsFeedController::class, 'getActivityStats']);
    $group->get('/news/project/{project}', [ProjectNewsFeedController::class, 'getProjectChangelog']);

    // Public Health Monitoring Routes (No Auth Required)
    $group->get('/health/system', [ProjectHealthController::class, 'getSystemHealth']);
    $group->get('/health/summary', [ProjectHealthController::class, 'getHealthSummary']);
    $group->get('/health/critical', [ProjectHealthController::class, 'getCriticalProjects']);
    $group->get('/health/recommendations', [ProjectHealthController::class, 'getRecommendations']);
    $group->get('/health/project/{project}', [ProjectHealthController::class, 'getProjectHealth']);
    $group->post('/health/check', [ProjectHealthController::class, 'runHealthCheck']);
    
    // Public Feature Request Routes (No Auth Required for viewing)
    $group->get('/features', [FeatureRequestController::class, 'getAllFeatures']);
    $group->get('/features/stats', [FeatureRequestController::class, 'getStats']);
    $group->get('/features/{id}', [FeatureRequestController::class, 'getFeatureById']);
    
    // Public Tracker Routes (Legacy - No Auth Required)
    $group->get('/tracker/stats', [TrackerController::class, 'getStats']);
    $group->get('/tracker/feature-requests', [TrackerController::class, 'getFeatureRequests']);
    $group->get('/tracker/project-suggestions', [TrackerController::class, 'getProjectSuggestions']);
    $group->get('/tracker/activity', [TrackerController::class, 'getActivityFeed']);
    $group->post('/tracker/feature-requests', [TrackerController::class, 'createFeatureRequest']);
    $group->post('/tracker/project-suggestions', [TrackerController::class, 'createProjectSuggestion']);
    $group->post('/tracker/vote', [TrackerController::class, 'vote']);
    // Local auth endpoints
    $group->post('/auth/login', [UserController::class, 'login']);
    $group->post('/auth/register', [UserController::class, 'register']);
    
    // Proxy auth endpoints to central Auth app (backup)
    $group->post('/auth/proxy/login', [\App\Controllers\AuthProxyController::class, 'login']);
    $group->post('/auth/proxy/register', [\App\Controllers\AuthProxyController::class, 'register']);
    $group->get('/auth/proxy/user', [\App\Controllers\AuthProxyController::class, 'getCurrentUser']);
    
    // Auth0 endpoints (Protected with Auth0 middleware)
    $group->group('/auth0', function (RouteCollectorProxy $auth0Group) {
        $auth0Group->post('/verify-user', [UserController::class, 'verifyAuth0User']);
    })->add(JwtAuthMiddleware::class);

    // Temporary (UNSECURED) admin endpoint to initialize the database for production
    // WARNING: This endpoint is intentionally unprotected. Remove it after use.
    $group->post('/admin/init-db', function ($request, $response) {
        try {
            Project::createTable();
            $payload = json_encode(['success' => true, 'message' => 'Database initialized (projects table created or already exists)']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $payload = json_encode(['success' => false, 'message' => 'Initialization failed', 'error' => $e->getMessage()]);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    });
    
    // Protected Routes (JWT Authentication Required)
    $group->group('', function (RouteCollectorProxy $protected) {
        // Protected project management routes
        $protected->post('/projects', [ProjectController::class, 'createProject']);
        $protected->put('/projects/{id}', [ProjectController::class, 'updateProject']);
        $protected->delete('/projects/{id}', [ProjectController::class, 'deleteProject']);
        
        // Protected feature request routes
        $protected->post('/features', [FeatureRequestController::class, 'createFeature']);
        $protected->post('/features/vote', [FeatureRequestController::class, 'voteOnFeature']);
        $protected->get('/users/{user_id}/features', [FeatureRequestController::class, 'getUserFeatures']);
        $protected->get('/users/{user_id}/votes', [FeatureRequestController::class, 'getUserVotes']);
        
        // User routes
        $protected->get('/user/profile', [UserController::class, 'getProfile']);
        $protected->put('/user/profile', [UserController::class, 'updateProfile']);
        $protected->post('/user/claim-daily-eggs', [UserController::class, 'claimDailyEggs']);
        $protected->get('/user/transactions', [UserController::class, 'getTransactions']);
        $protected->get('/user/dashboard', [UserController::class, 'getDashboard']);
        $protected->delete('/user/delete-account', [UserController::class, 'deleteAccount']);
        
        // Admin routes
        $protected->get('/admin/features/pending', [AdminController::class, 'getPendingFeatures']);
        $protected->post('/admin/features/{id}/approve', [AdminController::class, 'approveFeature']);
        $protected->post('/admin/features/{id}/reject', [AdminController::class, 'rejectFeature']);
        $protected->put('/admin/features/{id}/status', [AdminController::class, 'updateFeatureStatus']);
        $protected->post('/admin/features/bulk-approve', [AdminController::class, 'bulkApproveFeatures']);
        $protected->post('/admin/users/{id}/eggs', [AdminController::class, 'adjustUserEggs']);
        $protected->get('/admin/stats', [AdminController::class, 'getAdminStats']);
        $protected->get('/admin/users', [AdminController::class, 'getUserManagement']);
    })->add(JwtAuthMiddleware::class);
});
