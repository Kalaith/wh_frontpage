<?php
// init-database.php - Initialize Frontpage database (projects table)

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;
use App\Models\Project;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database setup
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

$startupMsg = "🚀 Initializing Frontpage Database (projects)...\n\n";
if (php_sapi_name() === 'cli') { echo $startupMsg; } else { error_log($startupMsg); }

try {
    // Create projects table
    Project::createTable();

    $msg = "\n✅ Projects table initialization completed successfully!\n";
    $note = "📊 You can now use the Frontpage API to manage projects.\n";
    if (php_sapi_name() === 'cli') { echo $msg; echo $note; } else { error_log($msg . $note); }

} catch (Exception $e) {
    $err = "\n❌ Database initialization failed: " . $e->getMessage() . "\n";
    if (php_sapi_name() === 'cli') { echo $err; } else { error_log($err); }
    exit(1);
}
