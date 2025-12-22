<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\ProjectRepository;

class GetProjectsByGroupAction
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

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

        $allProjects = $this->projectRepository->all();
        $groupProjects = array_filter($allProjects, function($project) use ($groupName) {
            return $project['group_name'] === $groupName;
        });

        return array_map(function ($project) use ($groupName) {
            $p = [
                'id' => $project['id'],
                'group_name' => $groupName,
                'title' => $project['title'],
                'description' => $project['description'],
                'stage' => $project['stage'],
                'status' => $project['status'],
                'version' => $project['version'],
            ];

            if ($project['path']) {
                $p['path'] = $project['path'];
            }
            if ($project['repository_url']) {
                $p['repository'] = ['url' => $project['repository_url']];
            }

            return $p;
        }, array_values($groupProjects));
    }
}
