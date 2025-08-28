<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
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

try {
    echo "Dropping tables with foreign key conflicts...\n";
    
    Capsule::schema()->dropIfExists('feature_votes');
    echo "✓ feature_votes table dropped\n";
    
    Capsule::schema()->dropIfExists('feature_approvals');
    echo "✓ feature_approvals table dropped\n";
    
    Capsule::schema()->dropIfExists('email_notifications');
    echo "✓ email_notifications table dropped\n";
    
    Capsule::schema()->dropIfExists('user_preferences');
    echo "✓ user_preferences table dropped\n";
    
    Capsule::schema()->dropIfExists('kofi_integrations');
    echo "✓ kofi_integrations table dropped\n";
    
    echo "\n✅ Tables dropped successfully!\n";
    echo "You can now re-run the create_feature_request_system.php script.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}