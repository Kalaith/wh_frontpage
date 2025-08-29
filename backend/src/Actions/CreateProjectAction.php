<?php

namespace App\Actions;

use App\Models\Project;

class CreateProjectAction
{
    public function execute(array $data): array
    {
        $project = Project::create([
            'title' => $data['title'],
            'path' => $data['path'] ?? null,
            'description' => $data['description'] ?? '',
            'stage' => $data['stage'] ?? 'prototype',
            'status' => $data['status'] ?? 'prototype',
            'version' => $data['version'] ?? '0.1.0',
            'group_name' => $data['group_name'] ?? 'other',
            'repository_type' => $data['repository']['type'] ?? null,
            'repository_url' => $data['repository']['url'] ?? null,
            'show_on_homepage' => $data['show_on_homepage'] ?? true
        ]);

        $created = [
            'id' => $project->id,
            'group_name' => $project->group_name,
            'title' => $project->title,
            'description' => $project->description,
            'stage' => $project->stage,
            'status' => $project->status,
            'version' => $project->version,
        ];

        if ($project->path) $created['path'] = $project->path;
        if ($project->repository_url) $created['repository'] = ['url' => $project->repository_url];

        return $created;
    }
}
