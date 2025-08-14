<?php
// index.php - Main entry point for Frontpage API

declare(strict_types=1);

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;
use App\Middleware\CorsMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Container setup
$container = new Container();
AppFactory::setContainer($container);

// Create Slim app
$app = AppFactory::create();

// Set base path for subdirectory deployment
if (isset($_ENV['APP_BASE_PATH'])) {
    $app->setBasePath($_ENV['APP_BASE_PATH']);
}
// For local development, don't set a base path (empty string means root)

// Add middleware
$app->add(new CorsMiddleware());
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// Error middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

// Handle preflight OPTIONS requests
$app->options('/{routes:.*}', function ($request, $response) {
    return $response;
});

// Error handler for JSON responses
$errorMiddleware->setDefaultErrorHandler(function ($request, $exception, $displayErrorDetails) {
    $response = new \Slim\Psr7\Response();
    
    $statusCode = 500;
    if (method_exists($exception, 'getCode') && $exception->getCode() >= 400 && $exception->getCode() < 600) {
        $statusCode = $exception->getCode();
    }
    
    $payload = json_encode([
        'success' => false,
        'error' => [
            'message' => $exception->getMessage(),
            'code' => $statusCode
        ]
    ]);
    
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($statusCode);
});

// Load routes
require_once __DIR__ . '/../src/Routes/api.php';

// Add a simple root endpoint for testing
$app->get('/', function ($request, $response) {
    $payload = json_encode([
        'success' => true,
        'message' => 'Frontpage API Server',
        'version' => '1.0.0',
        'endpoints' => [
            'GET /api/health' => 'Health check with detailed info',
            'GET /api/projects' => 'Get all projects',
            'GET /api/projects/{group}' => 'Get projects by group',
            'POST /api/projects' => 'Create new project'
        ],
        'timestamp' => date('c')
    ]);
    
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
