<?php
declare(strict_types=1);

// Database-free regression check: php backend/tests/project_update_visibility.php
require_once __DIR__ . '/../src/Repositories/ProjectRepository.php';
require_once __DIR__ . '/../src/Repositories/ProjectGitRepository.php';
require_once __DIR__ . '/../src/Services/ProjectCatalogNormalizer.php';
require_once __DIR__ . '/../src/Services/ProjectUpdateService.php';

use App\Repositories\ProjectRepository;
use App\Repositories\ProjectGitRepository;
use App\Services\ProjectUpdateService;

final class VisibilityStatement extends PDOStatement
{
    public function __construct(private array $rows = [], private mixed $column = false) {}

    public function execute(?array $params = null): bool
    {
        if ($params !== null) {
            $this->rows = array_values(array_filter(
                $this->rows,
                static fn (array $row): bool => in_array($row['project_id'], $params, true)
            ));
        }
        return true;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        return $this->column;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        return $this->rows;
    }
}

final class VisibilityDatabase extends PDO
{
    public function __construct(private array $projects) {}

    public function query(string $query, ?int $fetchMode = null, mixed ...$args): PDOStatement|false
    {
        if (str_starts_with($query, 'SHOW')) {
            return new VisibilityStatement(column: str_contains($query, 'project_roost_profiles') ? 'available' : false);
        }
        return new VisibilityStatement($this->projects);
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new VisibilityStatement(array_map(
            static fn (array $row): array => [
                'project_id' => $row['id'],
                'last_updated' => gmdate('Y-m-d\TH:i:s\Z', time() - $row['id'] * 60),
            ],
            $this->projects
        ));
    }
}

function project(int $id, array $overrides = []): array
{
    return array_replace([
        'id' => $id,
        'title' => "Project {$id}",
        'path' => "/project_{$id}/",
        'profile_slug' => "project_{$id}",
        'group_name' => 'apps',
        'description' => 'Example project',
        'stage' => 'React',
        'status' => 'MVP',
        'version' => '1',
        'repository_url' => null,
        'repository_type' => null,
        'hidden' => '0',
        'archived' => '0',
        'show_on_homepage' => 1,
    ], $overrides);
}

function service(array $projects): ProjectUpdateService
{
    $db = new VisibilityDatabase($projects);
    return new ProjectUpdateService(new ProjectRepository($db), new ProjectGitRepository($db));
}

$feed = service([
    project(1, ['title' => 'Frontpage', 'hidden' => '1']),
    project(2, ['archived' => '1']),
    project(3, ['profile_slug' => null]),
    project(4, ['group_name' => ' Private ']),
    project(5),
    // Hidden source duplicates used to win deduplication over visible rows.
    project(6, ['hidden' => true, 'path' => 'D:/WebHatchery/apps/project_5/']),
    // Homepage placement is independent of catalog visibility.
    project(7, ['show_on_homepage' => 0]),
    // A nameless deployment must not appear in the public update feed.
    project(8, ['title' => 'unknown_project', 'path' => null, 'profile_source' => 'deployment']),
]);
$updates = $feed->getAllProjectUpdates();
if (array_column($updates, 'name') !== ['Project 5', 'Project 7']) {
    throw new RuntimeException('The feed must contain only visible, active catalog projects in update order.');
}
if ($feed->getProjectStatistics()['total_projects'] !== 2) {
    throw new RuntimeException('Update statistics must use the same visibility rules.');
}
if (count($feed->getProjectsNeedingAttention()) !== 2) {
    throw new RuntimeException('Attention results must use the same visibility rules.');
}
if (service([project(1, ['archived' => true])])->getAllProjectUpdates() !== []) {
    throw new RuntimeException('A fully excluded catalog must produce an empty feed.');
}
echo "Project update visibility checks passed.\n";
