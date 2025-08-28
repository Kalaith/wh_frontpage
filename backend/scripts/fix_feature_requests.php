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
    echo "Fixing feature_requests table...\n";
    
    // First, add a default admin user if none exists
    $adminUser = Capsule::table('users')->where('role', 'admin')->first();
    if (!$adminUser) {
        $adminId = Capsule::table('users')->insertGetId([
            'username' => 'admin',
            'email' => 'admin@webhatchery.com',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'display_name' => 'Admin User',
            'role' => 'admin',
            'egg_balance' => 10000,
            'is_verified' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "✓ Created admin user with ID: $adminId\n";
    } else {
        $adminId = $adminUser->id;
        echo "✓ Admin user already exists with ID: $adminId\n";
    }
    
    // Update existing feature_requests to have valid user_id references
    $updated = Capsule::table('feature_requests')
        ->whereNull('submitted_by')
        ->orWhere('submitted_by', '')
        ->update(['submitted_by' => 'Anonymous']);
        
    echo "✓ Updated $updated feature requests with default submitted_by\n";
    
    // Now add the user_id column with the admin user as default
    if (!Capsule::schema()->hasColumn('feature_requests', 'user_id')) {
        // Add column without foreign key first
        Capsule::schema()->table('feature_requests', function ($table) use ($adminId) {
            $table->integer('user_id')->unsigned()->default($adminId)->after('id');
        });
        echo "✓ Added user_id column to feature_requests\n";
        
        // Now add the foreign key constraint
        Capsule::schema()->table('feature_requests', function ($table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
        echo "✓ Added foreign key constraint for user_id\n";
    }
    
    // Add other missing columns
    $columnsToAdd = [
        'use_case' => 'text',
        'expected_benefits' => 'text', 
        'priority_level' => "enum('low', 'medium', 'high') DEFAULT 'medium'",
        'feature_type' => "enum('enhancement', 'new_feature', 'bug_fix', 'ui_improvement', 'performance') DEFAULT 'enhancement'",
        'approval_notes' => 'text',
        'approved_by' => 'int unsigned',
        'approved_at' => 'timestamp',
        'total_eggs' => 'int DEFAULT 0',
        'vote_count' => 'int DEFAULT 0'
    ];
    
    foreach ($columnsToAdd as $column => $type) {
        if (!Capsule::schema()->hasColumn('feature_requests', $column)) {
            Capsule::statement("ALTER TABLE feature_requests ADD COLUMN $column $type NULL");
            echo "✓ Added $column column\n";
        }
    }
    
    // Add foreign key for approved_by if it doesn't exist
    try {
        Capsule::schema()->table('feature_requests', function ($table) {
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
        echo "✓ Added foreign key constraint for approved_by\n";
    } catch (Exception $e) {
        if (!str_contains($e->getMessage(), 'already exists')) {
            throw $e;
        }
        echo "✓ Foreign key for approved_by already exists\n";
    }
    
    echo "\n✅ Feature requests table fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

function now() {
    return date('Y-m-d H:i:s');
}