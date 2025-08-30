<?php
/**
 * Add project_id column to feature_requests table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Dotenv\Dotenv;

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

echo "Adding project_id column to feature_requests table...\n";

try {
    // Check if column already exists
    $hasColumn = Capsule::schema()->hasColumn('feature_requests', 'project_id');
    
    if (!$hasColumn) {
        Capsule::schema()->table('feature_requests', function (Blueprint $table) {
            $table->bigInteger('project_id')->nullable()->after('votes');
            $table->index('project_id');
        });
        
        echo "✅ Added project_id column to feature_requests table\n";
    } else {
        echo "ℹ️ project_id column already exists in feature_requests table\n";
    }

    echo "\n🎉 Migration completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Error adding project_id column: " . $e->getMessage() . "\n";
    exit(1);
}
?>