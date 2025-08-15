<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ProjectController;
use App\Middleware\JwtAuthMiddleware;

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
    
    // Protected Routes (JWT Authentication Required)
    $group->group('', function (RouteCollectorProxy $protected) {
        // Protected project management routes
        $protected->post('/projects', [ProjectController::class, 'createProject']);
    $protected->put('/projects/{id}', [ProjectController::class, 'updateProject']);
    $protected->delete('/projects/{id}', [ProjectController::class, 'deleteProject']);
    })->add(JwtAuthMiddleware::class);
});
