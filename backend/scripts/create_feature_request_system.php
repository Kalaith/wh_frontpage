<?php

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

try {
    echo "Creating feature request system tables...\n";

    // Users table for authentication
    if (!Capsule::schema()->hasTable('users')) {
        Capsule::schema()->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('display_name')->nullable();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->integer('egg_balance')->default(500); // New users get 500 eggs
            $table->timestamp('last_daily_reward')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
        echo "✓ Users table created\n";
    } else {
        echo "✓ Users table already exists\n";
    }

    // Egg transactions table
    if (!Capsule::schema()->hasTable('egg_transactions')) {
        Capsule::schema()->create('egg_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('amount'); // Can be negative for spending
            $table->enum('transaction_type', ['earn', 'spend', 'vote', 'daily_reward', 'registration_bonus', 'kofi_reward', 'admin_adjustment']);
            $table->string('description');
            $table->integer('reference_id')->nullable(); // For linking to feature requests, votes, etc.
            $table->string('reference_type')->nullable(); // 'feature_request', 'vote', etc.
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
        });
        echo "✓ Egg transactions table created\n";
    } else {
        echo "✓ Egg transactions table already exists\n";
    }

    // Update existing feature requests table to match new schema
    if (Capsule::schema()->hasTable('feature_requests')) {
        // Check if we need to add new columns
        if (!Capsule::schema()->hasColumn('feature_requests', 'user_id')) {
            Capsule::schema()->table('feature_requests', function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->default(1)->after('id');
                $table->text('use_case')->nullable()->after('description');
                $table->text('expected_benefits')->nullable()->after('use_case');
                $table->enum('priority_level', ['low', 'medium', 'high'])->default('medium')->after('expected_benefits');
                $table->enum('feature_type', ['enhancement', 'new_feature', 'bug_fix', 'ui_improvement', 'performance'])->default('enhancement')->after('priority_level');
                $table->text('approval_notes')->nullable()->after('status');
                $table->integer('approved_by')->unsigned()->nullable()->after('approval_notes');
                $table->timestamp('approved_at')->nullable()->after('approved_by');
                $table->integer('total_eggs')->default(0)->after('approved_at');
                $table->integer('vote_count')->default(0)->after('total_eggs');
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->index(['status', 'total_eggs']);
                $table->index(['project_id', 'status']);
            });
            echo "✓ Feature requests table updated with new columns\n";
        } else {
            echo "✓ Feature requests table already has required columns\n";
        }
    } else {
        Capsule::schema()->create('feature_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('project_id')->unsigned()->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable();
            $table->text('use_case')->nullable();
            $table->text('expected_benefits')->nullable();
            $table->enum('priority_level', ['low', 'medium', 'high'])->default('medium');
            $table->enum('feature_type', ['enhancement', 'new_feature', 'bug_fix', 'ui_improvement', 'performance'])->default('enhancement');
            $table->enum('status', ['pending', 'approved', 'open', 'planned', 'in_progress', 'completed', 'rejected'])->default('pending');
            $table->text('approval_notes')->nullable();
            $table->integer('approved_by')->unsigned()->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('total_eggs')->default(0);
            $table->integer('vote_count')->default(0);
            $table->json('tags')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'total_eggs']);
            $table->index(['project_id', 'status']);
            $table->fullText(['title', 'description']);
        });
        echo "✓ Feature requests table created\n";
    }

    // Feature votes table
    if (!Capsule::schema()->hasTable('feature_votes')) {
        Capsule::schema()->create('feature_votes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->bigInteger('feature_id')->unsigned(); // Match bigint from feature_requests
            $table->integer('eggs_allocated');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('feature_id')->references('id')->on('feature_requests')->onDelete('cascade');
            $table->unique(['user_id', 'feature_id']);
        });
        echo "✓ Feature votes table created\n";
    } else {
        echo "✓ Feature votes table already exists\n";
    }

    // Feature approvals table (admin actions log)
    if (!Capsule::schema()->hasTable('feature_approvals')) {
        Capsule::schema()->create('feature_approvals', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('feature_id')->unsigned(); // Match bigint from feature_requests
            $table->integer('admin_id')->unsigned();
            $table->enum('action', ['approve', 'reject', 'request_changes']);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('feature_id')->references('id')->on('feature_requests')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
        });
        echo "✓ Feature approvals table created\n";
    } else {
        echo "✓ Feature approvals table already exists\n";
    }

    // Email notifications table
    if (!Capsule::schema()->hasTable('email_notifications')) {
        Capsule::schema()->create('email_notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('type'); // 'feature_approved', 'feature_rejected', 'daily_reminder', 'weekly_digest'
            $table->string('subject');
            $table->text('message');
            $table->json('metadata')->nullable(); // Additional data for the notification
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['status', 'created_at']);
        });
        echo "✓ Email notifications table created\n";
    } else {
        echo "✓ Email notifications table already exists\n";
    }

    // User preferences table
    if (!Capsule::schema()->hasTable('user_preferences')) {
        Capsule::schema()->create('user_preferences', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('daily_egg_reminders')->default(true);
            $table->boolean('feature_approval_notifications')->default(true);
            $table->boolean('weekly_digest')->default(true);
            $table->string('timezone')->default('UTC');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
        });
        echo "✓ User preferences table created\n";
    } else {
        echo "✓ User preferences table already exists\n";
    }

    // Ko-fi integrations table
    if (!Capsule::schema()->hasTable('kofi_integrations')) {
        Capsule::schema()->create('kofi_integrations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('kofi_transaction_id')->unique();
            $table->string('email');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3);
            $table->enum('type', ['donation', 'subscription']);
            $table->boolean('is_monthly')->default(false);
            $table->integer('eggs_awarded')->default(0);
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['email', 'type']);
        });
        echo "✓ Ko-fi integrations table created\n";
    } else {
        echo "✓ Ko-fi integrations table already exists\n";
    }

    echo "\n✅ All feature request system tables created successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Update your API routes to include feature request endpoints\n";
    echo "2. Create the necessary controllers and models\n";
    echo "3. Implement the frontend components\n";
    echo "4. Set up email notification system\n";
    echo "5. Configure Ko-fi webhook integration\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}