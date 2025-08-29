<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

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
} else {
    echo "Database configuration not found in .env file.\n";
    exit(1);
}

try {
    echo "Making removed form fields nullable in feature_requests table...\n";
    
    // Make category, use_case, expected_benefits nullable
    Capsule::statement('ALTER TABLE feature_requests MODIFY category VARCHAR(255) NULL');
    echo "✓ Made category nullable\n";
    
    Capsule::statement('ALTER TABLE feature_requests MODIFY use_case TEXT NULL');
    echo "✓ Made use_case nullable\n";
    
    Capsule::statement('ALTER TABLE feature_requests MODIFY expected_benefits TEXT NULL');
    echo "✓ Made expected_benefits nullable\n";
    
    echo "Successfully updated feature_requests table to handle removed form fields!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}