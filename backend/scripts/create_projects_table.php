<?php
// scripts/create_projects_table.php - Database migration for projects table

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

echo "Creating projects table...\n";

try {
    Capsule::schema()->create('projects', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->string('path')->nullable();
        $table->text('description')->nullable();
        $table->string('stage')->default('prototype');
        $table->string('status')->default('prototype');
        $table->string('version')->default('0.1.0');
        $table->string('group_name')->default('other');
        $table->string('repository_type')->nullable();
        $table->string('repository_url')->nullable();
        $table->boolean('hidden')->default(false);
        $table->timestamps();
        
        $table->index(['group_name', 'hidden']);
        $table->index('status');
        $table->index('stage');
    });
    
    echo "✅ Projects table created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating projects table: " . $e->getMessage() . "\n";
    exit(1);
}
