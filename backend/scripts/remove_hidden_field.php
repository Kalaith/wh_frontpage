<?php

/**
 * Remove the 'hidden' field from projects table
 * This script removes the 'hidden' column since Project Visible logic has been removed
 */

require_once '../vendor/autoload.php';
require_once '../src/Config/database.php';

try {
    $schema = \Illuminate\Database\Capsule\Manager::schema();
    
    if ($schema->hasTable('projects')) {
        if ($schema->hasColumn('projects', 'hidden')) {
            $schema->table('projects', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn('hidden');
            });
            
            // Also drop the hidden index if it exists
            try {
                $schema->table('projects', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->dropIndex(['hidden']);
                });
            } catch (Exception $e) {
                // Index might not exist, ignore
            }
            
            echo "✅ Removed 'hidden' column from projects table\n";
        } else {
            echo "ℹ️  'hidden' column does not exist in projects table\n";
        }
    } else {
        echo "❌ Projects table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}