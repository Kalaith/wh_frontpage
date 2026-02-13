<?php
declare(strict_types=1);

namespace App\Models;

final class PerkToken
{
    public int $id;
    public int $adventurer_id;
    public string $perk_slug;
    public string $perk_name;
    public ?string $perk_effect = null;
    public bool $is_equipped = false;
    public ?string $expires_at = null;
    public string $created_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->adventurer_id = (int)($data['adventurer_id'] ?? 0);
            $this->perk_slug = (string)($data['perk_slug'] ?? '');
            $this->perk_name = (string)($data['perk_name'] ?? '');
            $this->perk_effect = $data['perk_effect'] ?? null;
            $this->is_equipped = (bool)($data['is_equipped'] ?? false);
            $this->expires_at = $data['expires_at'] ?? null;
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'adventurer_id' => $this->adventurer_id,
            'perk_slug' => $this->perk_slug,
            'perk_name' => $this->perk_name,
            'perk_effect' => $this->perk_effect,
            'is_equipped' => $this->is_equipped,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
