import { QuestChain } from '../types/QuestChain';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? '';

export async function fetchQuestChains(): Promise<QuestChain[]> {
  const res = await fetch(`${API_BASE}/api/quest-chains`);
  const json = await res.json();
  return json.data ?? [];
}

export async function fetchQuestChain(slug: string): Promise<QuestChain> {
  const res = await fetch(`${API_BASE}/api/quest-chains/${slug}`);
  const json = await res.json();
  return json.data;
}
