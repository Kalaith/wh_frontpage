<?php
declare(strict_types=1);

namespace App\Models;

final class Adventurer
{
    public int $id;
    public int $user_id;
    public string $github_username;
    public string $class = 'hatchling';
    public ?string $spec_primary = null;
    public ?string $spec_secondary = null;
    public int $xp_total = 0;
    public int $level = 1;
    public ?string $equipped_title = null;
    public int $glow_streak = 0;
    public string $created_at;
    public string $updated_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->user_id = (int)($data['user_id'] ?? 0);
            $this->github_username = (string)($data['github_username'] ?? '');
            $this->class = (string)($data['class'] ?? 'hatchling');
            $this->spec_primary = $data['spec_primary'] ?? null;
            $this->spec_secondary = $data['spec_secondary'] ?? null;
            $this->xp_total = (int)($data['xp_total'] ?? 0);
            $this->level = (int)($data['level'] ?? 1);
            $this->equipped_title = $data['equipped_title'] ?? null;
            $this->glow_streak = (int)($data['glow_streak'] ?? 0);
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
            $this->updated_at = (string)($data['updated_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'github_username' => $this->github_username,
            'class' => $this->class,
            'spec_primary' => $this->spec_primary,
            'spec_secondary' => $this->spec_secondary,
            'xp_total' => $this->xp_total,
            'level' => $this->level,
            'equipped_title' => $this->equipped_title,
            'glow_streak' => $this->glow_streak,
            'created_at' => $this->created_at,
        ];
    }
}
