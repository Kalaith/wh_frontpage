<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\ProjectRepository;
use App\Services\ProjectCatalogNormalizer;

class GetProjectsByGroupAction
{
    private ProjectCatalogNormalizer $catalogNormalizer;

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        ?ProjectCatalogNormalizer $catalogNormalizer = null
    ) {
        $this->catalogNormalizer = $catalogNormalizer ?? new ProjectCatalogNormalizer();
    }

    /**
     * @param string $groupName
     * @param bool $includePrivate
     * @return array
     */
    public function execute(string $groupName, bool $includePrivate = false): array
    {
        $normalizedGroupName = $this->catalogNormalizer->normalizeGroupName($groupName);

        if ($normalizedGroupName === 'private' && !$includePrivate) {
            return [];
        }

        $allProjects = $this->catalogNormalizer->deduplicateRows($this->projectRepository->all());
        $groupProjects = array_filter($allProjects, function ($project) use ($normalizedGroupName) {
            return $this->catalogNormalizer->normalizeGroupName((string)$project['group_name']) === $normalizedGroupName;
        });

        return array_map(function ($project) use ($normalizedGroupName) {
            $p = [
                'id' => $project['id'],
                'group_name' => $normalizedGroupName,
                'title' => $project['title'],
                'description' => $project['description'],
                'stage' => $this->catalogNormalizer->publicStage((string)$project['stage'], $normalizedGroupName),
                'status' => $this->catalogNormalizer->publicStatus((string)$project['status']),
                'version' => $project['version'],
            ];

            if ($project['path']) {
                $p['path'] = $this->catalogNormalizer->publicPath((string)$project['path']);
            }
            if ($project['repository_url']) {
                $p['repository'] = ['url' => $project['repository_url']];
            }

            return $p;
        }, array_values($groupProjects));
    }
}
