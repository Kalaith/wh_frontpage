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
    public int $phase = 1;
    public int $max_phase = 1;
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
            $this->phase = (int)($data['phase'] ?? 1);
            $this->max_phase = (int)($data['max_phase'] ?? 1);
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
            'phase' => $this->phase,
            'max_phase' => $this->max_phase,
            'hp_total' => $this->hp_total,
            'hp_current' => $this->hp_current,
            'status' => $this->status,
            'project_id' => $this->project_id,
            'season_id' => $this->season_id,
            'created_at' => $this->created_at,
            'defeated_at' => $this->defeated_at,
        ];
    }

    public function takeDamage(int $amount): void
    {
        if ($this->status === 'defeated') {
            return;
        }

        $this->hp_current -= $amount;

        if ($this->hp_current <= 0) {
            if ($this->phase < $this->max_phase) {
                $this->triggerPhaseTwo();
            } else {
                $this->hp_current = 0;
                $this->status = 'defeated';
                $this->defeated_at = date('Y-m-d H:i:s');
            }
        }
    }

    public function triggerPhaseTwo(): void
    {
        $this->phase++;
        $this->hp_total = (int)($this->hp_total * 1.5); // Scope creep adds 50% more total HP
        $this->hp_current = $this->hp_total; // Heal to full new max HP
        $this->status = 'active';
    }
}
