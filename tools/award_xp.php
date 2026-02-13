<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\AdventurerRepository;
use App\Services\GamificationService;

// Load environment variables if not in CI
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// Configuration
$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_DATABASE');
$dbUser = getenv('DB_USERNAME');
$dbPass = getenv('DB_PASSWORD');

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage() . "\n");
}

// Dependencies
$adventurerRepo = new AdventurerRepository($pdo);
$gamificationService = new GamificationService($pdo, $adventurerRepo);

// Get PR Data
$prUser = getenv('PR_USER');
$prNumber = getenv('PR_NUMBER');
$prLabelsJson = getenv('PR_LABELS');
$prLabels = json_decode($prLabelsJson, true) ?? [];

if (!$prUser) die("No PR_USER provided.\n");

echo "Processing PR #$prNumber for user: $prUser\n";

// Calculate XP
$baseXp = 50; // Merge bonus
$labelXp = 0;

foreach ($prLabels as $label) {
    $name = $label['name'];
    if (str_starts_with($name, 'xp:')) {
        $amount = match($name) {
            'xp:tiny' => 10,
            'xp:small' => 50,
            'xp:medium' => 200,
            'xp:large' => 500,
            'xp:epic' => 1000,
            default => 0,
        };
        $labelXp += $amount;
        echo "Found label: $name (+$amount XP)\n";
    }
}

$totalXp = $baseXp + $labelXp;
echo "Total XP to award: $totalXp\n";

// Find or Create Adventurer
$adventurer = $adventurerRepo->findByGitHubUsername($prUser);

if (!$adventurer) {
    echo "New adventurer discovered! Creating profile...\n";
    $stmt = $pdo->prepare("INSERT INTO adventurers (github_username, xp_total, level, class, created_at) VALUES (?, 0, 1, 'hatchling', NOW())");
    $stmt->execute([$prUser]);
    $adventurerId = (int)$pdo->lastInsertId();
} else {
    $adventurerId = $adventurer->id;
}

// Award XP via Service
try {
    $result = $gamificationService->awardXp($adventurerId, $totalXp, 'quest', "PR #$prNumber Merge");
    
    echo "XP Awarded successfully. New Total: {$result['new_xp']}\n";
    
    if ($result['leveled_up']) {
        echo "🎉 LEVEL UP! {$prUser} reached Level {$result['new_level']}!\n";
    }
    
    if (!empty($result['badges_earned'])) {
        foreach ($result['badges_earned'] as $badge) {
            echo "🏅 New Badge Earned: $badge\n";
        }
    }
} catch (Exception $e) {
    echo "Error awarding XP: " . $e->getMessage() . "\n";
    exit(1);
}
