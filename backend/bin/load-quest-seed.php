<?php

declare(strict_types=1);

/**
 * Load SMART quest seed JSON into WebHatchery gamification tables.
 *
 * Usage:
 *   php backend/bin/load-quest-seed.php --file=frontpage/seeds/quests/adventurer_guild_backend_cutover.json
 *   php backend/bin/load-quest-seed.php --dry-run
 */

$autoloader = null;
$searchPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
    __DIR__ . '/../../../../../vendor/autoload.php',
];

foreach ($searchPaths as $path) {
    if (file_exists($path)) {
        $autoloader = $path;
        break;
    }
}

if (!$autoloader) {
    fwrite(STDERR, "Autoloader not found. Run composer install first.\n");
    exit(1);
}

require_once $autoloader;

use Dotenv\Dotenv;

$options = getopt('', ['file:', 'dry-run']);
$seedFile = $options['file'] ?? (__DIR__ . '/../../seeds/quests/adventurer_guild_backend_cutover.json');
$dryRun = array_key_exists('dry-run', $options);

if (!file_exists($seedFile)) {
    fwrite(STDERR, "Seed file not found: {$seedFile}\n");
    exit(1);
}

$seedRaw = file_get_contents($seedFile);
if ($seedRaw === false) {
    fwrite(STDERR, "Failed to read seed file: {$seedFile}\n");
    exit(1);
}

$data = json_decode($seedRaw, true);
if (!is_array($data)) {
    fwrite(STDERR, "Invalid JSON in seed file: {$seedFile}\n");
    exit(1);
}

validateSeed($data);

$backendRoot = dirname(__DIR__);
if (class_exists(Dotenv::class) && file_exists($backendRoot . '/.env')) {
    Dotenv::createImmutable($backendRoot)->safeLoad();
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_DATABASE'] ?? 'webhatchery_frontpage';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

$dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$projectId = resolveProjectId($pdo, $data['habitat']);
if ($projectId === null) {
    fwrite(STDERR, "Could not resolve project_id for habitat. Check project_path/project_title in seed file.\n");
    exit(1);
}

$pdo->beginTransaction();

try {
    $seasonId = upsertSeason($pdo, $data['season']);

    $chainsInserted = 0;
    $bossesUpserted = 0;

    $chains = [];
    foreach ($data['quest_chains'] as $chain) {
        $chain['type'] = 'quest_chain';
        $chains[] = $chain;
    }
    foreach ($data['raids'] as $raid) {
        $raid['type'] = 'raid';
        $chains[] = $raid;
    }

    foreach ($chains as $chain) {
        upsertQuestChain($pdo, $chain, $seasonId);
        $chainsInserted++;
    }

    foreach ($data['bosses'] as $boss) {
        upsertBoss($pdo, $boss, $projectId, $seasonId);
        $bossesUpserted++;
    }

    if ($dryRun) {
        $pdo->rollBack();
        echo "Dry run complete. No changes committed.\n";
    } else {
        $pdo->commit();
        echo "Seed load complete.\n";
    }

    echo "Project ID: {$projectId}\n";
    echo "Season ID: {$seasonId}\n";
    echo "Quest chains/raids upserted: {$chainsInserted}\n";
    echo "Bosses upserted: {$bossesUpserted}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "Seed load failed: {$e->getMessage()}\n");
    exit(1);
}

function validateSeed(array $data): void
{
    $requiredTopKeys = ['habitat', 'season', 'quest_chains', 'raids', 'bosses'];
    foreach ($requiredTopKeys as $key) {
        if (!array_key_exists($key, $data)) {
            throw new RuntimeException("Missing required seed key: {$key}");
        }
    }

    if (!is_array($data['quest_chains']) || !is_array($data['raids']) || !is_array($data['bosses'])) {
        throw new RuntimeException('quest_chains, raids, and bosses must be arrays.');
    }
}

function resolveProjectId(PDO $pdo, array $habitat): ?int
{
    $path = $habitat['project_path'] ?? null;
    $title = $habitat['project_title'] ?? null;

    if (is_string($path) && $path !== '') {
        $stmt = $pdo->prepare('SELECT id FROM projects WHERE path = ? LIMIT 1');
        $stmt->execute([$path]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }
    }

    if (is_string($title) && $title !== '') {
        $stmt = $pdo->prepare('SELECT id FROM projects WHERE title = ? LIMIT 1');
        $stmt->execute([$title]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }
    }

    return null;
}

function upsertSeason(PDO $pdo, array $season): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO seasons (name, slug, starts_at, ends_at, is_active, path_chosen) VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           name = VALUES(name),
           starts_at = VALUES(starts_at),
           ends_at = VALUES(ends_at),
           is_active = VALUES(is_active),
           path_chosen = VALUES(path_chosen)'
    );

    $stmt->execute([
        $season['name'],
        $season['slug'],
        $season['starts_at'],
        $season['ends_at'],
        !empty($season['is_active']) ? 1 : 0,
        $season['path_chosen'] ?? null,
    ]);

    $idStmt = $pdo->prepare('SELECT id FROM seasons WHERE slug = ? LIMIT 1');
    $idStmt->execute([$season['slug']]);
    $row = $idStmt->fetch();

    if (!$row) {
        throw new RuntimeException('Failed to resolve season id after upsert.');
    }

    return (int)$row['id'];
}

function upsertQuestChain(PDO $pdo, array $chain, int $seasonId): void
{
    $steps = $chain['steps'] ?? [];
    if (!is_array($steps)) {
        throw new RuntimeException('Chain steps must be an array.');
    }

    $description = trim((string)($chain['description'] ?? ''));

    $metadata = [
        'type' => $chain['type'] ?? 'quest_chain',
        'labels' => $chain['labels'] ?? [],
        'entry_criteria' => $chain['entry_criteria'] ?? [],
        'go_no_go_gates' => $chain['go_no_go_gates'] ?? [],
    ];

    $descriptionWithMeta = $description;
    if (!empty($metadata['labels']) || !empty($metadata['entry_criteria']) || !empty($metadata['go_no_go_gates'])) {
        $descriptionWithMeta .= "\n\nMetadata: " . json_encode($metadata, JSON_UNESCAPED_SLASHES);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO quest_chains
            (slug, name, description, steps, total_steps, reward_xp, reward_badge_slug, reward_title, season_id, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            steps = VALUES(steps),
            total_steps = VALUES(total_steps),
            reward_xp = VALUES(reward_xp),
            reward_badge_slug = VALUES(reward_badge_slug),
            reward_title = VALUES(reward_title),
            season_id = VALUES(season_id),
            is_active = VALUES(is_active)'
    );

    $stmt->execute([
        $chain['slug'],
        $chain['name'],
        $descriptionWithMeta,
        json_encode($steps, JSON_UNESCAPED_SLASHES),
        count($steps),
        (int)($chain['reward_xp'] ?? 0),
        $chain['reward_badge_slug'] ?? null,
        $chain['reward_title'] ?? null,
        $seasonId,
        !empty($chain['is_active']) ? 1 : 0,
    ]);
}

function upsertBoss(PDO $pdo, array $boss, int $projectId, int $seasonId): void
{
    $description = buildBossDescription($boss);

    $existing = $pdo->prepare('SELECT id FROM bosses WHERE project_id <=> ? AND name = ? LIMIT 1');
    $existing->execute([$projectId, $boss['name']]);
    $row = $existing->fetch();

    $defeatedAt = (($boss['status'] ?? 'active') === 'defeated') ? date('Y-m-d H:i:s') : null;

    if ($row) {
        $update = $pdo->prepare(
            'UPDATE bosses
             SET github_issue_url = ?,
                 description = ?,
                 threat_level = ?,
                 status = ?,
                 project_id = ?,
                 season_id = ?,
                 hp_total = ?,
                 hp_current = ?,
                 defeated_at = ?
             WHERE id = ?'
        );

        $update->execute([
            $boss['github_issue_url'] ?? null,
            $description,
            (int)($boss['threat_level'] ?? 4),
            $boss['status'] ?? 'active',
            $projectId,
            $seasonId,
            (int)($boss['hp_total'] ?? 8),
            (int)($boss['hp_current'] ?? ($boss['hp_total'] ?? 8)),
            $defeatedAt,
            (int)$row['id'],
        ]);

        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO bosses
            (github_issue_url, name, description, threat_level, status, project_id, season_id, hp_total, hp_current, defeated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $insert->execute([
        $boss['github_issue_url'] ?? null,
        $boss['name'],
        $description,
        (int)($boss['threat_level'] ?? 4),
        $boss['status'] ?? 'active',
        $projectId,
        $seasonId,
        (int)($boss['hp_total'] ?? 8),
        (int)($boss['hp_current'] ?? ($boss['hp_total'] ?? 8)),
        $defeatedAt,
    ]);
}

function buildBossDescription(array $boss): string
{
    $base = trim((string)($boss['description'] ?? ''));

    $metadata = [
        'id' => $boss['id'] ?? null,
        'labels' => $boss['labels'] ?? [],
        'threat_type' => $boss['threat_type'] ?? null,
        'deadline' => $boss['deadline'] ?? null,
        'risk_level' => $boss['risk_level'] ?? null,
        'rollback_plan' => $boss['rollback_plan'] ?? null,
        'kill_criteria' => $boss['kill_criteria'] ?? [],
        'hp_tasks' => $boss['hp_tasks'] ?? [],
        'proof_required' => $boss['proof_required'] ?? [],
    ];

    return $base . "\n\nMetadata: " . json_encode($metadata, JSON_UNESCAPED_SLASHES);
}
