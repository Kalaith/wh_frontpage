<?php
declare(strict_types=1);

namespace App\Actions;

use App\Repositories\ProjectRepository;

class GetGroupedProjectsAction
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

    /**
     * @param bool $includePrivate Whether to include projects in the 'private' group
     */
    public function execute(bool $includePrivate = false): array
    {
        $projects = $this->projectRepository->all();
        
        $grouped = [];
        foreach ($projects as $project) {
            $groupName = $project['group_name'];

            // Skip private group when caller did not request private projects
            if (!$includePrivate && strtolower($groupName) === 'private') {
                continue;
            }
            
            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [
                    'name' => ucwords(str_replace('_', ' ', $groupName)),
                    'projects' => []
                ];
            }
            
            // Map flat database array to the structured format expected by components
            $projectData = [
                'id' => $project['id'],
                'group_name' => $groupName,
                'title' => $project['title'],
                'description' => $project['description'],
                'stage' => $project['stage'],
                'status' => $project['status'],
                'version' => $project['version'],
                'show_on_homepage' => (bool)$project['show_on_homepage'],
                'path' => $project['path'],
            ];
            
            if ($project['repository_url']) {
                $projectData['repository'] = [
                    'url' => $project['repository_url'],
                    'type' => $project['repository_type'] ?: 'git'
                ];
            }
            
            // Add metadata if it exists
            if ($project['last_updated']) $projectData['lastUpdated'] = $project['last_updated'];
            if ($project['last_build']) $projectData['lastBuild'] = $project['last_build'];
            if ($project['last_commit_message']) $projectData['lastCommitMessage'] = $project['last_commit_message'];
            if ($project['branch']) $projectData['branch'] = $project['branch'];
            if ($project['git_commit']) $projectData['gitCommit'] = $project['git_commit'];
            if ($project['environments']) $projectData['environments'] = json_decode($project['environments'], true);
            if ($project['project_type']) $projectData['type'] = $project['project_type'];
            
            $grouped[$groupName]['projects'][] = $projectData;
        }
        
        return $grouped;
    }
}
