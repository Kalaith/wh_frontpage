<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("Autoload not found at: $autoload");
}
require $autoload;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
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
    
    echo "Connected to DB\n";
    
    // Check ID 6
    $stmt = $pdo->query("SELECT * FROM project_suggestions WHERE id = 6");
    $row = $stmt->fetch();
    
    if ($row) {
        echo "ID 6 Found:\n";
        print_r($row);
    } else {
        echo "ID 6 NOT Found\n";
    }
    
    // List all IDs
    echo "\nAll Suggestion IDs:\n";
    $stmt = $pdo->query("SELECT id, name FROM project_suggestions");
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        echo "{$r['id']}: {$r['name']}\n";
    }

} catch (\PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
