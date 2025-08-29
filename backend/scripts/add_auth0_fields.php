<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
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
    echo "Adding Auth0 fields to users table...\n";
    
    // Check if columns already exist
    $hasAuth0Id = Capsule::schema()->hasColumn('users', 'auth0_id');
    $hasProvider = Capsule::schema()->hasColumn('users', 'provider');
    $hasEmailVerified = Capsule::schema()->hasColumn('users', 'email_verified');
    
    if ($hasAuth0Id && $hasProvider && $hasEmailVerified) {
        echo "Auth0 fields already exist in users table.\n";
        exit(0);
    }
    
    // Add the new columns
    Capsule::schema()->table('users', function (Blueprint $table) use ($hasAuth0Id, $hasProvider, $hasEmailVerified) {
        if (!$hasAuth0Id) {
            $table->string('auth0_id')->nullable()->unique()->after('email_verified_at');
            echo "Added auth0_id column\n";
        }
        
        if (!$hasProvider) {
            $table->string('provider')->default('local')->after('auth0_id');
            echo "Added provider column\n";
        }
        
        if (!$hasEmailVerified) {
            $table->boolean('email_verified')->default(false)->after('provider');
            echo "Added email_verified column\n";
        }
    });
    
    // Also make password_hash nullable for Auth0 users
    try {
        Capsule::statement('ALTER TABLE users MODIFY password_hash VARCHAR(255) NULL');
        echo "Made password_hash nullable for Auth0 users\n";
    } catch (Exception $e) {
        echo "Note: Could not modify password_hash column (it may already be nullable): " . $e->getMessage() . "\n";
    }
    
    echo "Successfully added Auth0 fields to users table!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}