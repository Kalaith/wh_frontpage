import { Quest } from '../types/Quest';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

export const fetchQuests = async (filters: { class?: string; difficulty?: number } = {}): Promise<Quest[]> => {
    const params = new URLSearchParams();
    if (filters.class) params.append('class', filters.class);
    if (filters.difficulty) params.append('difficulty', filters.difficulty.toString());

    const response = await fetch(`${API_BASE_URL}/quests?${params.toString()}`);
    if (!response.ok) {
        throw new Error('Failed to fetch quests');
    }

    const json = await response.json();
    return json.data || [];
};
