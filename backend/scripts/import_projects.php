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

echo "Importing projects from JSON...\n";

// Read the projects.json file
$jsonPath = __DIR__ . '/../../frontend/public/projects.json';
if (!file_exists($jsonPath)) {
    echo "❌ Error: projects.json file not found at $jsonPath\n";
    exit(1);
}

$jsonContent = file_get_contents($jsonPath);
$data = json_decode($jsonContent, true);

if (!$data || !isset($data['groups'])) {
    echo "❌ Error: Invalid JSON format\n";
    exit(1);
}

$importedCount = 0;

try {
    // Clear existing projects
    Project::truncate();
    echo "🗑️  Cleared existing projects\n";
    
    foreach ($data['groups'] as $groupKey => $group) {
        $groupName = $groupKey;
        $isHidden = isset($group['hidden']) && $group['hidden'];
        
        echo "📁 Processing group: $groupName\n";
        
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
            echo "  ✅ Imported: {$projectData['title']}\n";
        }
    }
    
    echo "\n🎉 Successfully imported $importedCount projects!\n";
    
} catch (Exception $e) {
    echo "❌ Error importing projects: " . $e->getMessage() . "\n";
    exit(1);
}
