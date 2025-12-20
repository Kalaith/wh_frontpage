declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;

class GetProjectsByGroupAction
{
    /**
     * @param string $groupName
     * @param bool $includePrivate
     * @return array
     */
    public function execute(string $groupName, bool $includePrivate = false): array
    {
        if (strtolower($groupName) === 'private' && !$includePrivate) {
            return [];
        }

        $projects = Project::where('group_name', $groupName)
            ->get();

        return (array)$projects->map(function ($project) use ($groupName) {
            $p = [
                'id' => $project->id,
                'group_name' => $groupName,
                'title' => $project->title,
                'description' => $project->description,
                'stage' => $project->stage,
                'status' => $project->status,
                'version' => $project->version,
            ];

            if ($project->path) {
                $p['path'] = $project->path;
            }
            if ($project->repository_url) {
                $p['repository'] = ['url' => $project->repository_url];
            }

            return $p;
        })->toArray();
    }
}
