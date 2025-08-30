<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;
use App\Services\EmailService;

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

// Process command line arguments
$options = getopt('', ['limit:', 'help', 'stats', 'test:', 'digest']);

if (isset($options['help'])) {
    echo "WebHatchery Email Queue Processor\n\n";
    echo "Usage: php process_email_queue.php [options]\n\n";
    echo "Options:\n";
    echo "  --limit=N      Process up to N notifications (default: 50)\n";
    echo "  --stats        Show queue statistics\n";
    echo "  --test=email   Send test email to specified address\n";
    echo "  --digest       Schedule weekly digest notifications\n";
    echo "  --help         Show this help message\n\n";
    exit(0);
}

$emailService = new EmailService();

try {
    // Show statistics
    if (isset($options['stats'])) {
        echo "📊 Email Queue Statistics\n";
        echo str_repeat("=", 40) . "\n";
        
        $stats = $emailService->getQueueStats();
        
        echo sprintf("Pending notifications: %d\n", $stats['pending']);
        echo sprintf("Sent today: %d\n", $stats['sent_today']);
        echo sprintf("Failed today: %d\n", $stats['failed_today']);
        echo sprintf("Total sent: %d\n", $stats['total_sent']);
        echo sprintf("Total failed: %d\n", $stats['total_failed']);
        echo "\n";
        
        if ($stats['pending'] > 0) {
            echo "✉️  Run without --stats to process pending notifications\n";
        } else {
            echo "✅ No pending notifications to process\n";
        }
        
        exit(0);
    }

    // Send test email
    if (isset($options['test'])) {
        $testEmail = $options['test'];
        
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            echo "❌ Invalid email address: {$testEmail}\n";
            exit(1);
        }
        
        echo "📧 Sending test email to: {$testEmail}\n";
        
        $result = $emailService->sendTestEmail($testEmail);
        
        if ($result['success']) {
            echo "✅ " . $result['message'] . "\n";
        } else {
            echo "❌ " . $result['message'] . "\n";
            exit(1);
        }
        
        exit(0);
    }

    // Schedule weekly digests
    if (isset($options['digest'])) {
        echo "📊 Scheduling weekly digest notifications...\n";
        
        $result = $emailService->scheduleWeeklyDigests();
        
        echo "✅ " . $result['message'] . "\n";
        exit(0);
    }

    // Process email queue
    $limit = isset($options['limit']) ? (int)$options['limit'] : 50;
    
    echo "📨 Processing email queue (limit: {$limit})...\n";
    
    $result = $emailService->sendPendingNotifications($limit);
    
    echo sprintf("✅ Sent: %d\n", $result['sent']);
    echo sprintf("❌ Failed: %d\n", $result['failed']);
    echo $result['message'] . "\n";
    
    if ($result['failed'] > 0) {
        echo "\n⚠️  Some notifications failed to send. Check the email_notifications table for error details.\n";
    }
    
    if ($result['sent'] + $result['failed'] === $limit) {
        echo "\n📝 Note: Limit reached. There may be more notifications to process.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    if (php_sapi_name() === 'cli') {
        echo "\nStack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
    
    exit(1);
}

echo "\n✨ Email processing completed!\n";