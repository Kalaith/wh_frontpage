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
    echo "Fixing tags format in feature_requests table...\n";
    
    // Get all feature requests with their current tags
    $requests = Capsule::table('feature_requests')->get();
    
    $fixedCount = 0;
    
    foreach ($requests as $request) {
        $currentTags = $request->tags;
        $newTags = null;
        
        // If tags is null or empty, set to empty array
        if ($currentTags === null || $currentTags === '') {
            $newTags = json_encode([]);
        }
        // If tags is a string, try to decode it
        else if (is_string($currentTags)) {
            // Check if it's already valid JSON
            $decoded = json_decode($currentTags, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // It's valid JSON, ensure it's an array
                if (!is_array($decoded)) {
                    $decoded = [];
                }
                $newTags = json_encode($decoded);
            } else {
                // It's not valid JSON, treat as a single tag if not empty
                if (trim($currentTags) !== '') {
                    $newTags = json_encode([trim($currentTags)]);
                } else {
                    $newTags = json_encode([]);
                }
            }
        }
        
        // Update the record if we need to change it
        if ($newTags !== null && $newTags !== $currentTags) {
            Capsule::table('feature_requests')
                ->where('id', $request->id)
                ->update(['tags' => $newTags]);
            
            echo "✓ Fixed tags for request ID {$request->id}: '{$currentTags}' -> '{$newTags}'\n";
            $fixedCount++;
        }
    }
    
    echo "\n✅ Fixed tags format for {$fixedCount} feature requests!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}