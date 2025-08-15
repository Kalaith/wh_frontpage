<?php
// scripts/import_projects.php - Import projects from JSON to database

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

$startupMsg = "Importing projects from JSON...\n";
if (php_sapi_name() === 'cli') { echo $startupMsg; } else { error_log($startupMsg); }

// Read the projects.json file
$jsonPath = __DIR__ . '/../../frontend/public/projects.json';
if (!file_exists($jsonPath)) {
    $err = "❌ Error: projects.json file not found at $jsonPath\n";
    if (php_sapi_name() === 'cli') { echo $err; } else { error_log($err); }
    exit(1);
}

$jsonContent = file_get_contents($jsonPath);
$data = json_decode($jsonContent, true);

if (!$data || !isset($data['groups'])) {
    $err = "❌ Error: Invalid JSON format\n";
    if (php_sapi_name() === 'cli') { echo $err; } else { error_log($err); }
    exit(1);
}

$importedCount = 0;

try {
    // Clear existing projects
    Project::truncate();
    $msg = "🗑️  Cleared existing projects\n";
    if (php_sapi_name() === 'cli') { echo $msg; } else { error_log($msg); }
    
    foreach ($data['groups'] as $groupKey => $group) {
        $groupName = $groupKey;
        $isHidden = isset($group['hidden']) && $group['hidden'];
        
    $msg = "📁 Processing group: $groupName\n";
    if (php_sapi_name() === 'cli') { echo $msg; } else { error_log($msg); }
        
        if (!isset($group['projects'])) {
            continue;
        }
        
        foreach ($group['projects'] as $projectData) {
            $project = Project::create([
                'title' => $projectData['title'],
                'path' => $projectData['path'] ?? null,
                'description' => $projectData['description'] ?? '',
                'stage' => $projectData['stage'] ?? 'prototype',
                'status' => $projectData['status'] ?? 'prototype',
                'version' => $projectData['version'] ?? '0.1.0',
                'group_name' => $groupName,
                'repository_type' => $projectData['repository']['type'] ?? null,
                'repository_url' => $projectData['repository']['url'] ?? null,
                'hidden' => $isHidden
            ]);
            
            $importedCount++;
            $msg = "  ✅ Imported: {$projectData['title']}\n";
            if (php_sapi_name() === 'cli') { echo $msg; } else { error_log($msg); }
        }
    }
    
    $msg = "\n🎉 Successfully imported $importedCount projects!\n";
    if (php_sapi_name() === 'cli') { echo $msg; } else { error_log($msg); }
    
} catch (Exception $e) {
    $err = "❌ Error importing projects: " . $e->getMessage() . "\n";
    if (php_sapi_name() === 'cli') { echo $err; } else { error_log($err); }
    exit(1);
}
