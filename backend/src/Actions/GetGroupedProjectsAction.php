declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;

class GetGroupedProjectsAction
{
    /**
     * @param bool $includePrivate Whether to include projects in the 'private' group
     */
    public function execute(bool $includePrivate = false): array
    {
        return (array)Project::getGroupedProjects($includePrivate);
    }
}
