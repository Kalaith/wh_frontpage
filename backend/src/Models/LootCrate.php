<?php
declare(strict_types=1);

namespace App\Models;

final class LootCrate
{
    public int $id;
    public int $adventurer_id;
    public string $rarity = 'common'; // common, uncommon, rare, epic, legendary
    public string $status = 'unopened'; // unopened, opened
    public ?string $source = null;
    public ?array $contents = null;
    public ?string $opened_at = null;
    public string $created_at;

    public function __construct(array $data = [])
    {
        if (!empty($data)) {
            $this->id = (int)($data['id'] ?? 0);
            $this->adventurer_id = (int)($data['adventurer_id'] ?? 0);
            $this->rarity = (string)($data['rarity'] ?? 'common');
            $this->status = (string)($data['status'] ?? 'unopened');
            $this->source = $data['source'] ?? null;
            $this->contents = is_string($data['contents'] ?? null) 
                ? json_decode($data['contents'], true) 
                : ($data['contents'] ?? null);
            $this->opened_at = $data['opened_at'] ?? null;
            $this->created_at = (string)($data['created_at'] ?? date('Y-m-d H:i:s'));
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'adventurer_id' => $this->adventurer_id,
            'rarity' => $this->rarity,
            'status' => $this->status,
            'source' => $this->source,
            'contents' => $this->contents,
            'opened_at' => $this->opened_at,
            'created_at' => $this->created_at,
        ];
    }
}
