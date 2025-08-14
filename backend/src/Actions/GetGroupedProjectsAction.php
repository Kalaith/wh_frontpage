<?php

namespace App\Actions;

use App\Models\Project;

class GetGroupedProjectsAction
{
    /**
     * @param bool $includePrivate Whether to include projects in the 'private' group
     */
    public function execute(bool $includePrivate = false): array
    {
        return Project::getGroupedProjects($includePrivate);
    }
}
