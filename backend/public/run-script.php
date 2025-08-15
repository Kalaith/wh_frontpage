<?php
// run-script.php - Secure, whitelisted runner for backend scripts
// Usage: POST /frontpage/public/run-script.php with form field `script=init-database` and header X-Admin-Key: <ADMIN_RUN_KEY>
// Note: This file intentionally limits what can be run. It checks ADMIN_RUN_KEY from environment and a safe whitelist.

declare(strict_types=1);

// Load composer autoload (required for Dotenv)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load .env if Dotenv is available
if (class_exists('\Dotenv\Dotenv')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    } catch (Exception $e) {
        // ignore
    }
}

// Helper to send JSON response
function jsonResponse(array $data, int $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed, use POST'], 405);
}

// Read admin key from header X-Admin-Key or HTTP_X_ADMIN_KEY
$adminKey = null;
if (function_exists('getallheaders')) {
    foreach (getallheaders() as $k => $v) {
        if (strtolower($k) === 'x-admin-key') {
            $adminKey = $v;
            break;
        }
    }
}
if (!$adminKey && isset($_SERVER['HTTP_X_ADMIN_KEY'])) {
    $adminKey = $_SERVER['HTTP_X_ADMIN_KEY'];
}

$expected = $_ENV['ADMIN_RUN_KEY'] ?? ($_SERVER['ADMIN_RUN_KEY'] ?? null);
if (!$expected) {
    jsonResponse(['success' => false, 'error' => 'Server not configured: ADMIN_RUN_KEY missing'], 500);
}

if (!$adminKey || !hash_equals((string)$expected, (string)$adminKey)) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

// Whitelist of allowed scripts (basename without .php)
$whitelist = [
    'init-database',
    'create_projects_table',
    'import_projects'
];

$script = $_POST['script'] ?? null;
if (!$script) {
    jsonResponse(['success' => false, 'error' => 'Missing script parameter'], 400);
}

// sanitize
$script = basename($script);
if (!in_array($script, $whitelist, true)) {
    jsonResponse(['success' => false, 'error' => 'Script not allowed'], 403);
}

$scriptPath = __DIR__ . '/../scripts/' . $script . '.php';
if (!file_exists($scriptPath)) {
    jsonResponse(['success' => false, 'error' => 'Script file not found'], 404);
}

// Run the script in a controlled subprocess using PHP CLI if available
$phpBinary = PHP_BINARY ?? null;
if ($phpBinary && is_executable($phpBinary)) {
    // Build command safely
    $cmd = escapeshellcmd($phpBinary) . ' ' . escapeshellarg($scriptPath) . ' 2>&1';
    // Execute and capture output
    $output = [];
    $returnVar = 0;
    exec($cmd, $output, $returnVar);
    jsonResponse([
        'success' => $returnVar === 0,
        'script' => $script,
        'exitCode' => $returnVar,
        'output' => implode("\n", $output)
    ], $returnVar === 0 ? 200 : 500);
}

// Fallback: include the script directly (risky but allowed when CLI not available)
ob_start();
try {
    include $scriptPath;
    $out = ob_get_clean();
    jsonResponse(['success' => true, 'script' => $script, 'output' => $out]);
} catch (Throwable $t) {
    $out = ob_get_clean();
    jsonResponse(['success' => false, 'script' => $script, 'error' => $t->getMessage(), 'output' => $out], 500);
}
