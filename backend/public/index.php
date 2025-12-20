<?php
// index.php - Main entry point for Frontpage API

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

// Search for shared vendor folder in multiple locations
$autoloader = null;
$searchPaths = [
    __DIR__ . '/../vendor/autoload.php',           // Local vendor
    __DIR__ . '/../../vendor/autoload.php',        // 2 levels up
    __DIR__ . '/../../../vendor/autoload.php',     // 3 levels up
    __DIR__ . '/../../../../vendor/autoload.php',  // 4 levels up
    __DIR__ . '/../../../../../vendor/autoload.php' // 5 levels up
];

foreach ($searchPaths as $path) {
    if (file_exists($path)) {
        $autoloader = $path;
        break;
    }
}

if (!$autoloader) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Autoloader not found. Please run 'composer install' or check your deployment.";
    exit(1);
}

require_once $autoloader;

// Manual autoloader for App classes - prepend to override stale composer mappings in preview
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
}, true, true);

// Load environment variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (\Throwable $e) {
    // Fail silently in some environments
}

// Get CORS origin
$allowedOrigin = $_ENV['CORS_ORIGIN'] ?? '*';

// Handle CORS preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

// Global CORS header for all other requests
header('Access-Control-Allow-Origin: ' . $allowedOrigin);

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
