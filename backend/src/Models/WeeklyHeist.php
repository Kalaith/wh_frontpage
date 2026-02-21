<?php

declare(strict_types=1);

namespace App\Models;

class WeeklyHeist
{
    public int $id;
    public string $goal;
    public int $target;
    public int $current;
    public int $participants;
    public string $reward;
    public string $starts_at;
    public string $ends_at;
    public bool $is_active;
    public ?string $created_at;

    public function __construct(array $data)
    {
        $this->id = (int)($data['id'] ?? 0);
        $this->goal = $data['goal'] ?? '';
        $this->target = (int)($data['target'] ?? 10);
        $this->current = (int)($data['current'] ?? 0);
        $this->participants = (int)($data['participants'] ?? 0);
        $this->reward = $data['reward'] ?? '';
        $this->starts_at = $data['starts_at'] ?? '';
        $this->ends_at = $data['ends_at'] ?? '';
        $this->is_active = (bool)($data['is_active'] ?? true);
        $this->created_at = $data['created_at'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'goal' => $this->goal,
            'target' => $this->target,
            'current' => $this->current,
            'participants' => $this->participants,
            'reward' => $this->reward,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at
        ];
    }
}
