<?php
declare(strict_types=1);

namespace App\Actions;

use App\Repositories\ProjectRepository;
use App\Repositories\ProjectGitRepository;
use App\Services\ProjectCatalogNormalizer;

class GetHomepageProjectsAction
{
    private ProjectCatalogNormalizer $catalogNormalizer;

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectGitRepository $projectGitRepository,
        ?ProjectCatalogNormalizer $catalogNormalizer = null
    ) {
        $this->catalogNormalizer = $catalogNormalizer ?? new ProjectCatalogNormalizer();
    }

    /**
     * @param bool $includePrivate Whether to include projects in the 'private' group
     */
    public function execute(bool $includePrivate = false): array
    {
        $projects = $this->catalogNormalizer->deduplicateRows(
            $this->projectRepository->getHomepageProjects()
        );
        
        // Collect project IDs and fetch all git metadata in one query
        $projectIds = array_column($projects, 'id');
        $gitMetadata = $this->projectGitRepository->findByProjectIds($projectIds);
        
        $grouped = [];
        foreach ($projects as $project) {
            $groupName = $this->catalogNormalizer->normalizeGroupName((string)$project['group_name']);

            // Skip private group when caller did not request private projects
            if (!$includePrivate && strtolower($groupName) === 'private') {
                continue;
            }
            
            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [
                    'name' => $this->catalogNormalizer->groupLabel($groupName),
                    'projects' => []
                ];
            }
            
            // Map flat database array to the structured format expected by components
            $projectData = [
                'id' => $project['id'],
                'group_name' => $groupName,
                'title' => $project['title'],
                'description' => $project['description'],
                'stage' => $this->catalogNormalizer->publicStage((string)$project['stage'], $groupName),
                'status' => $this->catalogNormalizer->publicStatus((string)$project['status']),
                'version' => $project['version'],
                'show_on_homepage' => (bool)$project['show_on_homepage'],
                'path' => $this->catalogNormalizer->publicPath($project['path'] ?? null),
            ];
            
            if ($project['repository_url'] ?? null) {
                $projectData['repository'] = [
                    'url' => $project['repository_url'],
                    'type' => $project['repository_type'] ?: 'git'
                ];
            }

            // Add project type from main table
            if ($project['project_type'] ?? null) {
                $projectData['type'] = $project['project_type'];
            }
            
            // Add git metadata from separate table
            $git = $gitMetadata[$project['id']] ?? null;
            if ($git) {
                if ($git['last_updated']) $projectData['lastUpdated'] = $git['last_updated'];
                if ($git['last_build']) $projectData['lastBuild'] = $git['last_build'];
                if ($git['last_commit_message']) $projectData['lastCommitMessage'] = $git['last_commit_message'];
                if ($git['branch']) $projectData['branch'] = $git['branch'];
                if ($git['git_commit']) $projectData['gitCommit'] = $git['git_commit'];
                if ($git['environments']) $projectData['environments'] = json_decode($git['environments'], true);
            }
            
            $grouped[$groupName]['projects'][] = $projectData;
        }
        
        return $grouped;
    }
}
