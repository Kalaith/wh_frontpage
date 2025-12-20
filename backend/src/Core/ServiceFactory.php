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
use App\Services\AuthService;
use App\Services\ProjectUpdateService;
use App\Services\ProjectNewsFeedService;
use App\Services\ProjectHealthService;
use RuntimeException;

final class ServiceFactory
{
    /** @var array<string, object> */
    private static array $instances = [];

    public function create(string $class): object
    {
        // Reusable services
        $projectUpdateService = new ProjectUpdateService();
        $projectNewsFeedService = new ProjectNewsFeedService($projectUpdateService);
        $projectHealthService = new ProjectHealthService($projectUpdateService);

        return match ($class) {
            UserController::class => new UserController(
                new LoginAction(),
                new RegisterAction(),
                new GetProfileAction(),
                new UpdateProfileAction()
            ),
            ProjectController::class => new ProjectController(
                new GetGroupedProjectsAction(),
                new GetProjectsByGroupAction(),
                new CreateProjectAction(),
                new UpdateProjectAction(),
                new DeleteProjectAction()
            ),
            TrackerController::class => new TrackerController(
                new GetTrackerStatsAction(),
                new GetFeatureRequestsAction(),
                new CreateFeatureRequestAction()
            ),
            AdminController::class => new AdminController(),
            AuthProxyController::class => new AuthProxyController(new AuthService()),
            FeatureRequestController::class => new FeatureRequestController(
                new GetAllFeaturesAction(),
                new CreateFeatureAction()
            ),
            ProjectHealthController::class => new ProjectHealthController($projectHealthService),
            ProjectNewsFeedController::class => new ProjectNewsFeedController($projectNewsFeedService),
            ProjectUpdateController::class => new ProjectUpdateController($projectUpdateService),
            default => throw new RuntimeException("Unknown class: $class")
        };
    }
}
