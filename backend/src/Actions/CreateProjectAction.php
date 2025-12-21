<?php

declare(strict_types=1);

namespace App\Actions;

use App\Repositories\ProjectRepository;

class CreateProjectAction
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

    public function execute(array $data): array
    {
        $id = $this->projectRepository->create([
            'title' => (string)$data['title'],
            'path' => $data['path'] ?? null,
            'description' => (string)($data['description'] ?? ''),
            'stage' => (string)($data['stage'] ?? 'prototype'),
            'status' => (string)($data['status'] ?? 'prototype'),
            'version' => (string)($data['version'] ?? '0.1.0'),
            'group_name' => (string)($data['group_name'] ?? 'other'),
            'repository_type' => $data['repository']['type'] ?? null,
            'repository_url' => $data['repository']['url'] ?? null,
            'show_on_homepage' => (bool)($data['show_on_homepage'] ?? true)
        ]);

        $project = $this->projectRepository->findById($id);

        if (!$project) {
            throw new \Exception('Failed to retrieve created project');
        }

        return $project;
    }
}
