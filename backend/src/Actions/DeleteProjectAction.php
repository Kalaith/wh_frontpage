<?php

namespace App\Actions;

use App\Models\Project;

class DeleteProjectAction
{
    public function execute(int $id): bool
    {
        $project = Project::find($id);
        if (!$project) return false;
        $project->delete();
        return true;
    }
}
