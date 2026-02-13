export interface LootCrate {
    id: number;
    adventurer_id: number;
    rarity: 'common' | 'uncommon' | 'rare' | 'epic' | 'legendary';
    status: 'unopened' | 'opened';
    source?: string;
    contents?: CrateContents;
    opened_at?: string;
    created_at: string;
}

export interface CrateContents {
    xp: number;
    badge?: { slug: string; name: string } | null;
    title?: string | null;
    perk?: string | null;
}

export interface LootTableInfo {
    rarity_weights: Record<string, string>;
    rewards: Record<string, Record<string, string>>;
}
