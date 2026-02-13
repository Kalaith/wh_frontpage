<?php
declare(strict_types=1);

namespace App\Models;

final class Boss
{
    public int $id;
    public string $name;
    public ?string $description = null;
    public ?string $github_issue_url = null;
    public int $threat_level = 3;
    public int $hp_total = 5000;
    public int $hp_current = 5000;
    public string $status = 'active'; // active, stabilizing, defeated
    public ?int $project_id = null;
    public ?int $season_id = null;
    public string $created_at;
    public ?string $defeated_at = null;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->name = (string)($data['name'] ?? '');
            $this->description = $data['description'] ?? null;
            $this->github_issue_url = $data['github_issue_url'] ?? null;
            $this->threat_level = (int)($data['threat_level'] ?? 3);
            $this->hp_total = (int)($data['hp_total'] ?? 5000);
            $this->hp_current = (int)($data['hp_current'] ?? 5000);
            $this->status = (string)($data['status'] ?? 'active');
            $this->project_id = isset($data['project_id']) ? (int)$data['project_id'] : null;
            $this->season_id = isset($data['season_id']) ? (int)$data['season_id'] : null;
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->defeated_at = $data['defeated_at'] ?? null;
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'github_issue_url' => $this->github_issue_url,
            'threat_level' => $this->threat_level,
            'hp_total' => $this->hp_total,
            'hp_current' => $this->hp_current,
            'status' => $this->status,
            'project_id' => $this->project_id,
            'season_id' => $this->season_id,
            'created_at' => $this->created_at,
            'defeated_at' => $this->defeated_at,
        ];
    }
}
