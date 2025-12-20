declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;

class UpdateProjectAction
{
    public function execute(int $id, array $data): ?array
    {
        $project = Project::find($id);
        if (!$project) {
            return null;
        }

        $project->title = $data['title'] ?? $project->title;
        $project->path = $data['path'] ?? $project->path;
        $project->description = $data['description'] ?? $project->description;
        $project->stage = $data['stage'] ?? $project->stage;
        $project->status = $data['status'] ?? $project->status;
        $project->version = $data['version'] ?? $project->version;
        $project->group_name = $data['group_name'] ?? $project->group_name;
        $project->repository_type = $data['repository']['type'] ?? $project->repository_type;
        $project->repository_url = $data['repository']['url'] ?? $project->repository_url;
        
        if (isset($data['show_on_homepage'])) {
            $project->show_on_homepage = (bool)$data['show_on_homepage'];
        }

        $project->save();

        $updated = [
            'id' => $project->id,
            'group_name' => $project->group_name,
            'title' => $project->title,
            'description' => $project->description,
            'stage' => $project->stage,
            'status' => $project->status,
            'version' => $project->version,
            'show_on_homepage' => $project->show_on_homepage,
        ];

        if ($project->path) {
            $updated['path'] = $project->path;
        }
        if ($project->repository_url) {
            $updated['repository'] = ['url' => $project->repository_url];
        }

        return (array)$updated;
    }
}
