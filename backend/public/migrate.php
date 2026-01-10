<?php
// migrate.php - Run database migrations via web request
// SECURITY WARNING: Delete this file after use in production!

declare(strict_types=1);

// Enable error reporting
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

echo "<h1>Database Migration Runner</h1>";
echo "<pre>";

try {
    // 1. Load the table creation script for comments
    echo "Running: create_suggestion_comments_table.php...\n";
    // We explicitly require the script. Note: The script uses dirname/../vendor etc, 
    // so we need to ensure current working directory or paths align.
    // The scripts use __DIR__ so they should be fine being included from here.
    // However, they both require autoload and load dotenv. 
    // Doing it twice might be okay or might cause notice.
    // Let's wrap them in a closure or just include them.
    // But they declare variables like $host in global scope.
    // To avoid collision, we can assume they set up variables and run.
    
    // We'll execute them one by one.
    
    // SCRIPT 1
    require_once __DIR__ . '/../scripts/create_suggestion_comments_table.php';
    echo "---------------------------------------------------\n";
    
    // SCRIPT 2
    // We need to reset/re-require for the second one? 
    // They both do `require vendor/autoload` and `Dotenv::create`.
    // require_once might skip the second one's inclusion of autoload (good).
    // But the variables $host etc are already set.
    // The second script sets them again.
    
    echo "Running: update_project_suggestions_table_schema.php...\n";
    require __DIR__ . '/../scripts/update_project_suggestions_table_schema.php'; 
    // Note: use require, not require_once, because we want it to run even if included before (though unlikely).
    // Actually, manual_migration.php was the "create comments table" one? 
    // Wait, let's check which file is which.
    // Step 85: create_suggestion_comments_table.php
    // Step 232: update_project_suggestions_table_schema.php
    // Step 78: manual_migration.php (was for comments table too? Let's check step 81/85)
    // Step 85 created create_suggestion_comments_table.php.
    // Step 78 created scripts/manual_migration.php which effectively did the same thing? 
    // Step 152 says "Created manual migration script to create the project_suggestion_comments table".
    // It seems I have duplicate scripts or similar ones.
    // I should run `create_suggestion_comments_table.php` (comments) and `update_project_suggestions_table_schema.php` (columns).
    
    echo "---------------------------------------------------\n";
    echo "Done.\n";
    
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
echo "<p><strong>Migration process finished. Please delete this file from your server after successful execution.</strong></p>";
