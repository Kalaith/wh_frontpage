<?php
// index.php - Main entry point for Frontpage API

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

// Handle CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database setup
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

// Create Router
$router = new Router();

// Set base path if needed (for subdirectory deployment)
if (isset($_ENV['APP_BASE_PATH']) && !empty($_ENV['APP_BASE_PATH'])) {
    $router->setBasePath($_ENV['APP_BASE_PATH']);
}

// Load routes
require_once __DIR__ . '/../src/Routes/api.php';

// Add a simple root endpoint for testing
$router->get('/', function (Request $request, Response $response) {
    $response->success([
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
});

// Run Router
$router->handle();
