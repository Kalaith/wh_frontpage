<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\UserController;
use App\Controllers\ProjectController;
use App\Controllers\TrackerController;
use App\Controllers\AdminController;
use App\Controllers\AuthProxyController;
use App\Controllers\FeatureRequestController;
use App\Controllers\ProjectHealthController;
use App\Controllers\ProjectNewsFeedController;
use App\Controllers\ProjectUpdateController;
use App\Actions\CreateProjectAction;
use App\Actions\UpdateProjectAction;
use App\Actions\DeleteProjectAction;
use App\Actions\GetGroupedProjectsAction;
use App\Actions\GetProjectsByGroupAction;
use App\Actions\GetTrackerStatsAction;
use App\Actions\GetFeatureRequestsAction;
use App\Actions\CreateFeatureRequestAction;
use App\Actions\LoginAction;
use App\Actions\RegisterAction;
use App\Actions\GetProfileAction;
use App\Actions\UpdateProfileAction;
use App\Actions\GetAllFeaturesAction;
use App\Actions\CreateFeatureAction;
use App\Actions\GetHomepageProjectsAction;
use App\Services\AuthService;
use App\Services\ProjectUpdateService;
use App\Services\ProjectNewsFeedService;
use App\Services\ProjectHealthService;
use RuntimeException;

final class ServiceFactory
{
    /** @var array<string, object> */
    private static array $instances = [];

    private static ?\PDO $db = null;

    private function getDb(): \PDO
    {
        if (self::$db !== null) {
            return self::$db;
        }

        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $db   = $_ENV['DB_DATABASE'] ?? 'webhatchery';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        self::$db = new \PDO($dsn, $user, $pass, $options);
        return self::$db;
    }

    public function create(string $class): object
    {
        $db = $this->getDb();

        // Repositories
        $projectRepo = new \App\Repositories\ProjectRepository($db);
        $projectGitRepo = new \App\Repositories\ProjectGitRepository($db);
        $userRepo = new \App\Repositories\UserRepository($db);
        $featureRepo = new \App\Repositories\FeatureRequestRepository($db);
        $voteRepo = new \App\Repositories\FeatureVoteRepository($db);
        $activityRepo = new \App\Repositories\ActivityFeedRepository($db);
        $suggestionRepo = new \App\Repositories\ProjectSuggestionRepository($db);
        $eggRepo = new \App\Repositories\EggTransactionRepository($db);
        $notificationRepo = new \App\Repositories\EmailNotificationRepository($db);

        // Reusable services
        if (isset(self::$instances[$class])) {
            return self::$instances[$class];
        }

        // Shared services
        $updateService = new \App\Services\ProjectUpdateService($projectRepo, $projectGitRepo);

        $instance = match ($class) {
            ProjectController::class => new ProjectController(
                new \App\Actions\GetGroupedProjectsAction($projectRepo, $projectGitRepo),
                new \App\Actions\GetProjectsByGroupAction($projectRepo),
                new \App\Actions\CreateProjectAction($projectRepo),
                new \App\Actions\UpdateProjectAction($projectRepo),
                new \App\Actions\DeleteProjectAction($projectRepo),
                new \App\Actions\GetHomepageProjectsAction($projectRepo, $projectGitRepo)
            ),
            TrackerController::class => new TrackerController(
                new \App\Actions\GetTrackerStatsAction($projectRepo, $featureRepo, $suggestionRepo),
                new \App\Actions\GetFeatureRequestsAction($featureRepo),
                new \App\Actions\CreateFeatureRequestAction($featureRepo, $activityRepo)
            ),
            UserController::class => new UserController(
                new \App\Actions\LoginAction($userRepo),
                new \App\Actions\RegisterAction($userRepo),
                new \App\Actions\GetProfileAction($userRepo, $featureRepo, $voteRepo, $eggRepo),
                new \App\Actions\UpdateProfileAction($userRepo)
            ),
            AuthProxyController::class => new AuthProxyController(
                new \App\Services\AuthService()
            ),
            FeatureRequestController::class => new FeatureRequestController(
                new \App\Actions\GetAllFeaturesAction($featureRepo),
                new \App\Actions\CreateFeatureAction($featureRepo, $activityRepo, $userRepo, $eggRepo)
            ),
            ProjectUpdateController::class => new ProjectUpdateController($updateService),
            ProjectNewsFeedController::class => new ProjectNewsFeedController(
                new \App\Services\ProjectNewsFeedService($updateService)
            ),
            ProjectHealthController::class => new ProjectHealthController(
                new \App\Services\ProjectHealthService($updateService)
            ),
            AdminController::class => new AdminController(
                $userRepo,
                $featureRepo,
                $voteRepo,
                $eggRepo,
                $notificationRepo
            ),
            \App\Controllers\GitHubWebhookController::class => new \App\Controllers\GitHubWebhookController(
                $projectRepo,
                $projectGitRepo
            ),
            default => throw new \Exception("Unknown class: $class")
        };

        self::$instances[$class] = $instance;
        return $instance;
    }
}
