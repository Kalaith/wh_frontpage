<?php
declare(strict_types=1);

namespace App\Models;

/**
 * ProjectSuggestionComment Data Transfer Object
 */
final class ProjectSuggestionComment
{
    public int $id;
    public int $project_suggestion_id;
    public ?int $user_id = null;
    public ?string $user_name = null;
    public string $content;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->project_suggestion_id = (int)($data['project_suggestion_id'] ?? 0);
            $this->user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
            $this->user_name = $data['user_name'] ?? null;
            $this->content = (string)($data['content'] ?? '');
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_suggestion_id' => $this->project_suggestion_id,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
