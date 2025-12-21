<?php

declare(strict_types=1);

// Search for shared vendor folder in multiple locations
$autoloader = null;
$searchPaths = [
    __DIR__ . '/../vendor/autoload.php',           // Local vendor
    __DIR__ . '/../../vendor/autoload.php',        // Parent level
    __DIR__ . '/../../../vendor/autoload.php',     // 2 levels up
    __DIR__ . '/../../../../vendor/autoload.php',  // 3 levels up
    __DIR__ . '/../../../../../vendor/autoload.php' // 4 levels up
];

foreach ($searchPaths as $path) {
    if (file_exists($path)) {
        $autoloader = $path;
        break;
    }
}

if (!$autoloader) {
    die("❌ Autoloader not found. Please run 'composer install' in the appropriate directory.\n");
}

require_once $autoloader;

// Manual autoloader for App classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
}, true, true);

use App\Repositories\ProjectRepository;
use Dotenv\Dotenv;

function log_msg(string $msg) {
    file_put_contents('h:/WebHatchery/frontpage/backend/sync.log', "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR))) {
        log_msg("❌ SHUTDOWN ERROR: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});

log_msg("🚀 Starting execution...");

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
    log_msg("✅ Env loaded.");

    // Initialize PDO
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $db   = $_ENV['DB_DATABASE'] ?? 'frontpage';
    $user = $_ENV['DB_USERNAME'] ?? 'root';
    $pass = $_ENV['DB_PASSWORD'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $charset = 'utf8mb4';

    log_msg("🔌 Attempting connection to $db on $host...");
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    log_msg("✅ PDO connected.");

    $projectRepo = new ProjectRepository($pdo);

log_msg("🚀 Starting Project Synchronization...");

// Incremental Migration System
function runMigrations(PDO $pdo): void {
    log_msg("🔄 Running migrations...");
    
    // 1. Create migrations table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $appliedMigrations = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

    $migrations = [
        '2024_01_01_000001_create_projects_table' => "
            CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                path VARCHAR(255),
                description TEXT,
                stage VARCHAR(50),
                status VARCHAR(50),
                version VARCHAR(50),
                group_name VARCHAR(50),
                repository_type VARCHAR(50),
                repository_url VARCHAR(255),
                show_on_homepage TINYINT(1) DEFAULT 1,
                last_updated DATETIME,
                last_build DATETIME,
                last_commit_message TEXT,
                branch VARCHAR(100),
                git_commit VARCHAR(100),
                environments JSON,
                project_type VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        '2024_01_01_000002_create_users_table' => "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) DEFAULT 'user',
                display_name VARCHAR(100),
                egg_balance INT DEFAULT 0,
                is_verified TINYINT(1) DEFAULT 0,
                last_daily_claim TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        '2024_01_01_000003_create_feature_requests_table' => "
            CREATE TABLE IF NOT EXISTS feature_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                status VARCHAR(50) DEFAULT 'pending',
                group_name VARCHAR(50),
                tags JSON,
                total_eggs INT DEFAULT 0,
                vote_count INT DEFAULT 0,
                approved_by INT NULL,
                approved_at TIMESTAMP NULL,
                approval_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        '2024_01_01_000004_create_feature_votes_table' => "
            CREATE TABLE IF NOT EXISTS feature_votes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                feature_id INT NOT NULL,
                user_id INT NOT NULL,
                eggs_allocated INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_vote (feature_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        '2024_01_01_000005_create_egg_transactions_table' => "
            CREATE TABLE IF NOT EXISTS egg_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                amount INT NOT NULL,
                transaction_type VARCHAR(50) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        '2024_01_01_000006_create_activity_feed_table' => "
            CREATE TABLE IF NOT EXISTS activity_feed (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,
                action VARCHAR(100) NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                reference_id INT,
                reference_type VARCHAR(50),
                user VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        '2024_01_01_000007_create_project_suggestions_table' => "
            CREATE TABLE IF NOT EXISTS project_suggestions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                suggested_group VARCHAR(50),
                rationale TEXT,
                votes INT DEFAULT 0,
                status VARCHAR(50) DEFAULT 'Suggested',
                submitted_by VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
        '2024_01_01_000008_create_email_notifications_table' => "
            CREATE TABLE IF NOT EXISTS email_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT,
                metadata JSON,
                status VARCHAR(20) DEFAULT 'pending',
                sent_at TIMESTAMP NULL,
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ",
    ];

    foreach ($migrations as $name => $sql) {
        if (!in_array($name, $appliedMigrations)) {
            log_msg("📜 Applying migration: $name...");
            $pdo->exec($sql);
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:name)");
            $stmt->execute(['name' => $name]);
            log_msg("✅ Migration $name applied.");
        }
    }
}

try {
    runMigrations($pdo);
} catch (\Exception $e) {
    log_msg("❌ Migration failed: " . $e->getMessage());
    throw $e;
}

// Temporary scan-only project update service for synchronization
class ProjectSyncService {
    private array $projectPaths;

    public function __construct() {
        $this->projectPaths = [
            'preview' => $_ENV['PREVIEW_ROOT'] ?? 'H:\\xampp\\htdocs',
            'production' => $_ENV['PRODUCTION_ROOT'] ?? 'F:\\WebHatchery'
        ];
    }

    public function scanFilesystem(): array {
        $projects = [];
        foreach ($this->projectPaths as $environment => $basePath) {
            if (!is_dir($basePath)) continue;
            log_msg("📂 Scanning $basePath...");
            $projectDirs = (array)glob($basePath . '/*', GLOB_ONLYDIR);
            foreach ($projectDirs as $projectDir) {
                $projectName = basename($projectDir);
                if (in_array($projectName, ['backend', 'vendor', 'storage', 'logs', 'tmp'])) continue;
                $manifestPath = $projectDir . '/project.json';
                if (file_exists($manifestPath)) {
                    $manifest = json_decode(file_get_contents($manifestPath), true);
                    if ($manifest) {
                        $manifest['path'] = $projectDir;
                        $manifest['environment'] = $environment;
                        $manifest['deployedName'] = $projectName;
                        $uniqueKey = $projectName . '_' . $environment;
                        $projects[$uniqueKey] = $manifest;
                    }
                }
            }
        }
        return $this->mergeEnvironments(array_values($projects));
    }

    private function mergeEnvironments(array $projects): array {
        $merged = [];
        foreach ($projects as $project) {
            $name = $project['deployedName'] ?? $project['name'] ?? 'unknown';
            if (!isset($merged[$name])) {
                $merged[$name] = $project;
                $merged[$name]['environments'] = [$project['environment']];
            } else {
                if (!in_array($project['environment'], $merged[$name]['environments'])) {
                    $merged[$name]['environments'][] = $project['environment'];
                }
                if ($project['environment'] === 'production') {
                    $merged[$name] = array_merge($merged[$name], $project);
                    $merged[$name]['environments'] = array_unique(array_merge($merged[$name]['environments'], [$project['environment']]));
                }
            }
        }
        return array_values($merged);
    }
}

$syncService = new ProjectSyncService();
$filesystemProjects = $syncService->scanFilesystem();

log_msg("🔍 Found " . count($filesystemProjects) . " projects in filesystem.");

$syncedCount = 0;
$updatedCount = 0;

foreach ($filesystemProjects as $p) {
    $projectName = $p['deployedName'] ?? $p['name'] ?? 'unknown';
    
    // Find project by title or path
    $project = $projectRepo->findByTitle($projectName);
    if (!$project) {
        $project = $projectRepo->findByPathLike($projectName);
    }
    
    $data = [
        'title' => $projectName,
        'path' => $p['path'] ?? null,
        'description' => $p['description'] ?? '',
        'version' => $p['version'] ?? '0.1.0',
        'project_type' => $p['type'] ?? 'apps',
        'last_updated' => $p['lastUpdated'] ?? null,
        'last_build' => $p['lastBuild'] ?? null,
        'last_commit_message' => $p['lastCommitMessage'] ?? null,
        'branch' => $p['branch'] ?? null,
        'git_commit' => $p['gitCommit'] ?? null,
        'environments' => $p['environments'] ?? [],
        'group_name' => $p['type'] ?? 'other'
    ];

    if (!$project) {
        log_msg("➕ Creating new project: $projectName");
        $projectRepo->create($data);
    } else {
        log_msg("🔄 Updating existing project: {$project['title']}");
        $projectRepo->update($project['id'], $data);
        $updatedCount++;
    }

    $syncedCount++;
}

log_msg("✨ Synchronization Complete!");
log_msg("Total processed: $syncedCount");
log_msg("Newly created: " . ($syncedCount - $updatedCount));
log_msg("Updated: $updatedCount");

} catch (Throwable $e) {
    log_msg("❌ FATAL ERROR: " . $e->getMessage());
    log_msg("Stack trace:\n" . $e->getTraceAsString());
    exit(1);
}
