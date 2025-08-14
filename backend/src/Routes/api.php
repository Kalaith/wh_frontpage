<?php
// src/Routes/api.php - API Routes

// Health check
$app->get('/api/health', function ($request, $response) {
    try {
        $payload = json_encode([
            'success' => true,
            'message' => 'API is healthy',
            'timestamp' => date('c'),
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
            'timestamp' => date('c')
        ]);
        
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

// Temporary simple projects endpoint for testing
$app->get('/api/projects', function ($request, $response) {
    $payload = json_encode([
        'success' => true,
        'data' => [
            'version' => '2.0.0',
            'description' => 'WebHatchery Projects API',
            'groups' => [
                'test' => [
                    'name' => 'Test Projects',
                    'projects' => [
                        [
                            'title' => 'Test Project',
                            'description' => 'A test project from the API',
                            'stage' => 'API',
                            'status' => 'working',
                            'version' => '1.0.0'
                        ]
                    ]
                ]
            ]
        ]
    ]);
    
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});
