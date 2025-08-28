<?php
// index.php - Main entry point for Frontpage API

declare(strict_types=1);

use Slim\Factory\AppFactory;
use DI\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;
use App\Middleware\CorsMiddleware;

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database setup (optional for frontpage, but consistent with auth)
if (isset($_ENV['DB_HOST'])) {
    $capsule = new Capsule;
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'],
        'database' => $_ENV['DB_DATABASE'],
        'username' => $_ENV['DB_USERNAME'],
        'password' => $_ENV['DB_PASSWORD'],
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();
}

// Container setup
$container = new Container();

// Register dependencies
$container->set(\App\Controllers\ProjectController::class, function() {
    return new \App\Controllers\ProjectController();
});

$container->set(\App\Controllers\TrackerController::class, function() {
    return new \App\Controllers\TrackerController();
});

$container->set(\App\Middleware\JwtAuthMiddleware::class, function() {
    return new \App\Middleware\JwtAuthMiddleware();
});

AppFactory::setContainer($container);

// Create Slim app
$app = AppFactory::create();

// Debug: Log request information
error_log("Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set'));
error_log("Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set'));
error_log("Path Info: " . ($_SERVER['PATH_INFO'] ?? 'not set'));
error_log("APP_BASE_PATH: " . ($_ENV['APP_BASE_PATH'] ?? 'not set'));

// Set base path for subdirectory deployment
if (isset($_ENV['APP_BASE_PATH']) && !empty($_ENV['APP_BASE_PATH'])) {
    $app->setBasePath($_ENV['APP_BASE_PATH']);
    error_log("Slim base path set to: " . $_ENV['APP_BASE_PATH']);
} else {
    error_log("No base path set");
}

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
            'POST /api/projects' => 'Create new project',
            'GET /api/tracker/stats' => 'Get tracker statistics',
            'GET /api/tracker/feature-requests' => 'Get feature requests',
            'GET /api/tracker/project-suggestions' => 'Get project suggestions',
            'GET /api/tracker/activity' => 'Get activity feed',
            'POST /api/tracker/feature-requests' => 'Create feature request',
            'POST /api/tracker/project-suggestions' => 'Create project suggestion',
            'POST /api/tracker/vote' => 'Vote on item'
        ],
        'timestamp' => date('c')
    ]);
    
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
