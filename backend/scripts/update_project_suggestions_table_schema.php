<?php
require __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_DATABASE'];
$user = $_ENV['DB_USERNAME'];
$pass = $_ENV['DB_PASSWORD'];
$port = $_ENV['DB_PORT'];
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Check if user_id column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM project_suggestions LIKE 'user_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE project_suggestions ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER id");
        $pdo->exec("CREATE INDEX idx_user_id ON project_suggestions(user_id)");
        echo "Added user_id column.\n";
    } else {
        echo "user_id column already exists.\n";
    }

    // Check if rationale column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM project_suggestions LIKE 'rationale'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE project_suggestions ADD COLUMN rationale TEXT NULL AFTER description");
        echo "Added rationale column.\n";
    } else {
        echo "rationale column already exists.\n";
    }

    echo "Table schema update completed.\n";
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}
