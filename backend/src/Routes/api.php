<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ProjectController;
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
    $group->get('/projects/{group}', [ProjectController::class, 'getProjectsByGroup']);
    // Proxy auth endpoints to central Auth app
    $group->post('/auth/login', [\App\Controllers\AuthProxyController::class, 'login']);
    $group->post('/auth/register', [\App\Controllers\AuthProxyController::class, 'register']);
    $group->get('/auth/user', [\App\Controllers\AuthProxyController::class, 'getCurrentUser']);

    // Temporary secure admin endpoint to initialize the database.
    // Requires an 'X-Init-Key' header matching the INIT_DB_KEY environment variable.
    // Remove or restrict this endpoint after use.
    $group->post('/admin/init-db', function ($request, $response) {
        $envKey = $_ENV['INIT_DB_KEY'] ?? null;
        $providedKey = $request->getHeaderLine('X-Init-Key');

        if (!$envKey) {
            $payload = json_encode(['success' => false, 'message' => 'INIT_DB_KEY not configured on server']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        if (!$providedKey || !hash_equals($envKey, $providedKey)) {
            $payload = json_encode(['success' => false, 'message' => 'Invalid init key']);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

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
    })->add(JwtAuthMiddleware::class);
});
