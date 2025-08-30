<?php
/**
 * Create tracker tables for feature requests and project suggestions
 * This script creates the necessary database tables for the tracker functionality
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

$startupMsg = "Creating tracker tables...\n";
if (php_sapi_name() === 'cli') { echo $startupMsg; } else { error_log($startupMsg); }

try {
    // Create feature requests table
    if (!Capsule::schema()->hasTable('feature_requests')) {
        Capsule::schema()->create('feature_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 100)->default('Enhancement');
            $table->string('priority', 20)->default('Medium');
            $table->string('status', 20)->default('Open');
            $table->json('tags')->nullable(); // JSON array of tags
            $table->integer('votes')->default(0);
            $table->bigInteger('project_id')->nullable(); // Link to specific project
            $table->string('submitted_by', 100)->nullable(); // User who submitted
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('priority');
            $table->index('category');
            $table->index('votes');
            $table->index('project_id');
            
            // Foreign key constraint
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
        
        echo "✅ Created feature_requests table\n";
    } else {
        echo "ℹ️ feature_requests table already exists\n";
    }

    // Create project suggestions table
    if (!Capsule::schema()->hasTable('project_suggestions')) {
        Capsule::schema()->create('project_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('suggested_group', 100)->default('Web Applications');
            $table->text('rationale')->nullable();
            $table->integer('votes')->default(0);
            $table->string('status', 20)->default('Suggested'); // Suggested, Under Review, Approved, Rejected
            $table->string('submitted_by', 100)->nullable(); // User who submitted
            $table->timestamps();
            
            // Indexes
            $table->index('suggested_group');
            $table->index('status');
            $table->index('votes');
        });
        
        echo "✅ Created project_suggestions table\n";
    } else {
        echo "ℹ️ project_suggestions table already exists\n";
    }

    // Create activity feed table
    if (!Capsule::schema()->hasTable('activity_feed')) {
        Capsule::schema()->create('activity_feed', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // feature_request, project_suggestion, project_update
            $table->string('action', 50); // created, updated, completed, voted
            $table->string('title');
            $table->text('description')->nullable();
            $table->bigInteger('reference_id')->nullable(); // ID of the related entity
            $table->string('reference_type', 50)->nullable(); // feature_request, project_suggestion, project
            $table->string('user', 100)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
        });
        
        echo "✅ Created activity_feed table\n";
    } else {
        echo "ℹ️ activity_feed table already exists\n";
    }

    // Create votes tracking table
    if (!Capsule::schema()->hasTable('votes')) {
        Capsule::schema()->create('votes', function (Blueprint $table) {
            $table->id();
            $table->string('voter_ip', 45); // IP address for anonymous voting
            $table->string('voter_id', 100)->nullable(); // User ID if authenticated
            $table->bigInteger('item_id'); // ID of voted item
            $table->string('item_type', 50); // feature_request, project_suggestion
            $table->tinyInteger('vote_value')->default(1); // 1 for upvote, -1 for downvote
            $table->timestamps();
            
            // Unique constraint to prevent duplicate votes
            $table->unique(['voter_ip', 'item_id', 'item_type'], 'unique_ip_vote');
            $table->unique(['voter_id', 'item_id', 'item_type'], 'unique_user_vote');
            
            // Indexes
            $table->index(['item_type', 'item_id']);
        });
        
        echo "✅ Created votes table\n";
    } else {
        echo "ℹ️ votes table already exists\n";
    }

    // Insert some sample data
    insertSampleData();

    echo "\n🎉 All tracker tables created successfully!\n";
    echo "You can now use the tracker functionality in the frontend.\n";

} catch (Exception $e) {
    echo "❌ Error creating tracker tables: " . $e->getMessage() . "\n";
    exit(1);
}

function insertSampleData() {
    // Check if we already have sample data
    $existingRequests = Capsule::table('feature_requests')->count();
    $existingSuggestions = Capsule::table('project_suggestions')->count();
    
    if ($existingRequests === 0) {
        // Insert sample feature requests
        Capsule::table('feature_requests')->insert([
            [
                'title' => 'Dark Mode Support',
                'description' => 'Add dark mode theme support across all applications for better user experience during night usage.',
                'category' => 'Enhancement',
                'priority' => 'High',
                'status' => 'Open',
                'tags' => json_encode(['UI', 'Theme']),
                'votes' => 23,
                'submitted_by' => 'community',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'title' => 'Mobile Responsive Design',
                'description' => 'Improve mobile responsiveness for the project showcase and management pages.',
                'category' => 'UI/UX Improvement',
                'priority' => 'Medium',
                'status' => 'In Progress',
                'tags' => json_encode(['Mobile', 'Responsive']),
                'votes' => 18,
                'submitted_by' => 'community',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
            ],
            [
                'title' => 'Export Project Data',
                'description' => 'Allow users to export project data in JSON or CSV format for backup and analysis purposes.',
                'category' => 'New Feature',
                'priority' => 'Medium',
                'status' => 'Open',
                'tags' => json_encode(['Export', 'Data']),
                'votes' => 15,
                'submitted_by' => 'community',
                'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))
            ]
        ]);
        echo "✅ Inserted sample feature requests\n";
    }
    
    if ($existingSuggestions === 0) {
        // Insert sample project suggestions
        Capsule::table('project_suggestions')->insert([
            [
                'name' => 'Interactive Fiction Engine',
                'description' => 'A web-based tool for creating and playing interactive fiction stories with branching narratives.',
                'suggested_group' => 'Fiction Projects',
                'rationale' => 'Would complement the existing story projects and provide a platform for interactive storytelling.',
                'votes' => 19,
                'status' => 'Suggested',
                'submitted_by' => 'community',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'name' => 'Game Development Toolkit',
                'description' => 'A suite of tools for indie game developers including asset management and game analytics.',
                'suggested_group' => 'Game Design',
                'rationale' => 'Would expand the game development portfolio and attract developer community.',
                'votes' => 16,
                'status' => 'Under Review',
                'submitted_by' => 'community',
                'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'name' => 'AI Code Assistant',
                'description' => 'An AI-powered coding assistant specifically trained on WebHatchery project patterns and conventions.',
                'suggested_group' => 'AI & Development Tools',
                'rationale' => 'Would improve development efficiency and help maintain code consistency across projects.',
                'votes' => 24,
                'status' => 'Approved',
                'submitted_by' => 'community',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ]
        ]);
        echo "✅ Inserted sample project suggestions\n";
    }

    // Insert activity feed entries
    $existingActivity = Capsule::table('activity_feed')->count();
    if ($existingActivity === 0) {
        Capsule::table('activity_feed')->insert([
            [
                'type' => 'feature_request',
                'action' => 'created',
                'title' => 'Added new feature request',
                'description' => 'Dark Mode Support',
                'reference_type' => 'feature_request',
                'reference_id' => 1,
                'user' => 'community',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'type' => 'feature_request',
                'action' => 'updated',
                'title' => 'Started working on feature',
                'description' => 'Mobile Responsive Design',
                'reference_type' => 'feature_request', 
                'reference_id' => 2,
                'user' => 'developer',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'type' => 'project_suggestion',
                'action' => 'created',
                'title' => 'New project suggested',
                'description' => 'AI Code Assistant',
                'reference_type' => 'project_suggestion',
                'reference_id' => 3,
                'user' => 'community',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
            ]
        ]);
        echo "✅ Inserted sample activity feed entries\n";
    }
}
?>