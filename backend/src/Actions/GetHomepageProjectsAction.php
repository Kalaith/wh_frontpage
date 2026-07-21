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
     * Build the homepage payload sourced solely from the project_roost catalog.
     *
     * @param bool $includePrivate Whether to include projects in the 'private' group
     * @return array{groups: array<string, array{name: string, projects: array<int, array<string, mixed>>}>, archived: array<int, array<string, mixed>>}
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
        $archived = [];

        foreach ($projects as $project) {
            $groupName = $this->catalogNormalizer->normalizeGroupName((string)$project['group_name']);

            // Skip private group when caller did not request private projects
            if (!$includePrivate && strtolower($groupName) === 'private') {
                continue;
            }

            $projectData = $this->mapProject($project, $groupName, $gitMetadata);

            // Archived projects are surfaced in a dedicated section, not the grid.
            if (!empty($project['archived'])) {
                $archived[] = $projectData;
                continue;
            }

            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [
                    'name' => $this->catalogNormalizer->groupLabel($groupName),
                    'projects' => []
                ];
            }

            $grouped[$groupName]['projects'][] = $projectData;
        }

        return [
            'groups' => $grouped,
            'archived' => $archived,
        ];
    }

    /**
     * Map a flat catalog row into the structured shape the frontend expects,
     * sourcing display fields from the project_roost profile.
     *
     * @param array<string, mixed> $project
     * @param array<int, array<string, mixed>> $gitMetadata
     * @return array<string, mixed>
     */
    private function mapProject(array $project, string $groupName, array $gitMetadata): array
    {
        $projectData = [
            'id' => $project['id'],
            'group_name' => $groupName,
            'title' => $project['title'],
            'display_name' => $this->catalogNormalizer->publicDisplayName($project['display_name'] ?? null, (string)$project['title']),
            'description' => $this->catalogNormalizer->publicDescription($project),
            'stage' => $this->catalogNormalizer->publicStage((string)$project['stage'], $groupName),
            'status' => $this->catalogNormalizer->publicStatus((string)$project['status']),
            'version' => $project['version'],
            'show_on_homepage' => (bool)$project['show_on_homepage'],
            'archived' => (bool)($project['archived'] ?? false),
            'path' => $this->catalogNormalizer->publicPathForRow($project),
        ];

        if ($project['repository_url'] ?? null) {
            $projectData['repository'] = [
                'url' => $project['repository_url'],
                'type' => $project['repository_type'] ?: 'git'
            ];
        }

        // Prefer the project_roost profile category, fall back to the project type.
        if ($project['profile_category'] ?? null) {
            $projectData['type'] = $project['profile_category'];
        } elseif ($project['project_type'] ?? null) {
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

        return $projectData;
    }
}
