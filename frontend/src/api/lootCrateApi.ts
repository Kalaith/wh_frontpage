import { LootCrate, LootTableInfo, CrateContents } from '../types/LootCrate';

const API_BASE = import.meta.env.VITE_API_BASE_URL || '';

export async function fetchCratePreview(): Promise<LootTableInfo> {
    const res = await fetch(`${API_BASE}/api/crates/preview`);
    const json = await res.json();
    return json.data;
}

export async function fetchAdventurerCrates(username: string): Promise<LootCrate[]> {
    const res = await fetch(`${API_BASE}/api/adventurers/${username}/crates`);
    const json = await res.json();
    return json.data?.crates ?? [];
}

export async function openCrate(crateId: number, adventurerId: number): Promise<CrateContents> {
    const res = await fetch(`${API_BASE}/api/crates/${crateId}/open`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ adventurer_id: adventurerId }),
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.message || 'Failed to open crate');
    return json.data.contents;
}
