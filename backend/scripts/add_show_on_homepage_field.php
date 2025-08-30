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
    echo "Adding show_on_homepage field to projects table...\n";
    
    // Check if the column already exists
    if (!Capsule::schema()->hasColumn('projects', 'show_on_homepage')) {
        Capsule::schema()->table('projects', function ($table) {
            $table->boolean('show_on_homepage')->default(true)->after('hidden');
        });
        echo "✅ Added show_on_homepage column to projects table\n";
    } else {
        echo "ℹ️  show_on_homepage column already exists\n";
    }
    
    // Set all existing projects to show on homepage by default
    $updated = Capsule::table('projects')
        ->whereNull('show_on_homepage')
        ->update(['show_on_homepage' => true]);
    
    if ($updated > 0) {
        echo "✅ Set $updated existing projects to show on homepage by default\n";
    }
    
    echo "\n🎉 Successfully added show_on_homepage functionality!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}