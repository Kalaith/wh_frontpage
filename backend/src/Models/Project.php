<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Project Data Transfer Object
 * Previously an Eloquent model, now a simple data structure.
 */
final class Project
{
    public int $id;
    public string $title;
    public ?string $path = null;
    public ?string $description = null;
    public string $stage = 'prototype';
    public string $status = 'prototype';
    public string $version = '0.1.0';
    public string $group_name = 'other';
    public ?string $repository_type = null;
    public ?string $repository_url = null;
    public bool $show_on_homepage = true;
    public ?string $last_updated = null;
    public ?string $last_build = null;
    public ?string $last_commit_message = null;
    public ?string $branch = null;
    public ?string $git_commit = null;
    public array $environments = [];
    public ?string $project_type = null;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->title = (string)($data['title'] ?? '');
            $this->path = $data['path'] ?? null;
            $this->description = $data['description'] ?? null;
            $this->stage = (string)($data['stage'] ?? 'prototype');
            $this->status = (string)($data['status'] ?? 'prototype');
            $this->version = (string)($data['version'] ?? '0.1.0');
            $this->group_name = (string)($data['group_name'] ?? 'other');
            $this->repository_type = $data['repository_type'] ?? null;
            $this->repository_url = $data['repository_url'] ?? null;
            $this->show_on_homepage = (bool)($data['show_on_homepage'] ?? true);
            $this->last_updated = $data['last_updated'] ?? null;
            $this->last_build = $data['last_build'] ?? null;
            $this->last_commit_message = $data['last_commit_message'] ?? null;
            $this->branch = $data['branch'] ?? null;
            $this->git_commit = $data['git_commit'] ?? null;
            $this->project_type = $data['project_type'] ?? null;
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));

            if (isset($data['environments'])) {
                $this->environments = is_string($data['environments']) 
                    ? json_decode($data['environments'], true) 
                    : (array)$data['environments'];
            }
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'path' => $this->path,
            'description' => $this->description,
            'stage' => $this->stage,
            'status' => $this->status,
            'version' => $this->version,
            'group_name' => $this->group_name,
            'repository_type' => $this->repository_type,
            'repository_url' => $this->repository_url,
            'show_on_homepage' => $this->show_on_homepage,
            'last_updated' => $this->last_updated,
            'last_build' => $this->last_build,
            'last_commit_message' => $this->last_commit_message,
            'branch' => $this->branch,
            'git_commit' => $this->git_commit,
            'environments' => $this->environments,
            'project_type' => $this->project_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
