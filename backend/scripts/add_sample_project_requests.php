<?php
/**
 * Add sample feature requests linked to specific projects
 */

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

echo "Adding sample project-specific feature requests...\n";

try {
    // Get some project IDs from the database
    $frontpageProject = Capsule::table('projects')->where('title', 'Frontpage')->first();
    $litrpgProject = Capsule::table('projects')->where('title', 'LitRPG Studio')->first();
    $dungeonCoreProject = Capsule::table('projects')->where('title', 'Dungeon Core')->first();

    $sampleRequests = [];

    if ($frontpageProject) {
        $sampleRequests[] = [
            'title' => 'Add Project Search Functionality',
            'description' => 'Add a search bar to quickly find projects by name or description in the main project showcase.',
            'category' => 'New Feature',
            'priority' => 'High',
            'status' => 'Open',
            'tags' => json_encode(['search', 'ui', 'frontend']),
            'votes' => 12,
            'project_id' => $frontpageProject->id,
            'submitted_by' => 'community',
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ];

        $sampleRequests[] = [
            'title' => 'Improve Mobile Navigation',
            'description' => 'The mobile navigation menu needs better touch targets and improved accessibility for mobile users.',
            'category' => 'UI/UX Improvement',
            'priority' => 'Medium',
            'status' => 'In Progress',
            'tags' => json_encode(['mobile', 'navigation', 'accessibility']),
            'votes' => 8,
            'project_id' => $frontpageProject->id,
            'submitted_by' => 'developer',
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ];
    }

    if ($litrpgProject) {
        $sampleRequests[] = [
            'title' => 'Character Stat Calculator',
            'description' => 'Add an integrated calculator for character stats that automatically updates when level or equipment changes.',
            'category' => 'New Feature',
            'priority' => 'High',
            'status' => 'Open',
            'tags' => json_encode(['calculator', 'stats', 'character']),
            'votes' => 15,
            'project_id' => $litrpgProject->id,
            'submitted_by' => 'author_user',
            'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
        ];

        $sampleRequests[] = [
            'title' => 'Export to PDF',
            'description' => 'Allow authors to export their character sheets and progression charts as PDF documents for reference.',
            'category' => 'New Feature',
            'priority' => 'Medium',
            'status' => 'Open',
            'tags' => json_encode(['export', 'pdf', 'printing']),
            'votes' => 9,
            'project_id' => $litrpgProject->id,
            'submitted_by' => 'community',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ];
    }

    if ($dungeonCoreProject) {
        $sampleRequests[] = [
            'title' => 'Monster AI Improvements',
            'description' => 'Improve the monster AI behavior patterns to make dungeon encounters more challenging and varied.',
            'category' => 'Enhancement',
            'priority' => 'High',
            'status' => 'Open',
            'tags' => json_encode(['ai', 'monsters', 'gameplay']),
            'votes' => 21,
            'project_id' => $dungeonCoreProject->id,
            'submitted_by' => 'player',
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ];

        $sampleRequests[] = [
            'title' => 'Save Game Functionality',
            'description' => 'Add the ability to save and load game progress so players can continue their dungeon building sessions.',
            'category' => 'New Feature',
            'priority' => 'Critical',
            'status' => 'In Progress',
            'tags' => json_encode(['save', 'persistence', 'core']),
            'votes' => 28,
            'project_id' => $dungeonCoreProject->id,
            'submitted_by' => 'developer',
            'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))
        ];
    }

    // Insert the sample requests
    foreach ($sampleRequests as $request) {
        Capsule::table('feature_requests')->insert($request);
    }

    echo "✅ Added " . count($sampleRequests) . " project-specific feature requests\n";
    echo "\n🎉 Sample data added successfully!\n";

} catch (Exception $e) {
    echo "❌ Error adding sample requests: " . $e->getMessage() . "\n";
    exit(1);
}
?>