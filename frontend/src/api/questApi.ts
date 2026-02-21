import { Quest, QuestAcceptance, RankProgress } from '../types/Quest';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api';

function getAuthToken(): string | null {
    const authStorage = localStorage.getItem('auth-storage');
    if (authStorage) {
        try {
            const parsed = JSON.parse(authStorage) as { state?: { token?: string | null } };
            const tokenFromStore = parsed?.state?.token;
            if (tokenFromStore) return tokenFromStore;
        } catch (error) {
            console.error('Failed to parse auth-storage', error);
        }
    }
    return null;
}

function authHeaders(): HeadersInit {
    const token = getAuthToken();
    const headers: Record<string, string> = { 'Content-Type': 'application/json' };
    if (token) headers['Authorization'] = `Bearer ${token}`;
    return headers;
}

export const fetchQuests = async (filters: { class?: string; difficulty?: number } = {}): Promise<Quest[]> => {
    const params = new URLSearchParams();
    if (filters.class) params.append('class', filters.class);
    if (filters.difficulty) params.append('difficulty', filters.difficulty.toString());

    const response = await fetch(`${API_BASE_URL}/quests?${params.toString()}`);
    if (!response.ok) {
        throw new Error('Failed to fetch quests');
    }

    const json = await response.json();
    return json.data ?? [];
};

export const acceptQuest = async (questRef: string, rankRequired?: string): Promise<{ status: string; message: string }> => {
    const response = await fetch(`${API_BASE_URL}/quests/${encodeURIComponent(questRef)}/accept`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({ rank_required: rankRequired }),
    });
    const json = await response.json();
    if (!response.ok) {
        throw new Error(json.error ?? 'Failed to accept quest');
    }
    return json.data;
};

export const submitQuest = async (questRef: string, prUrl: string): Promise<{ status: string; message: string }> => {
    const response = await fetch(`${API_BASE_URL}/quests/${encodeURIComponent(questRef)}/submit`, {
        method: 'POST',
        headers: authHeaders(),
        body: JSON.stringify({ pr_url: prUrl }),
    });
    const json = await response.json();
    if (!response.ok) {
        throw new Error(json.error ?? 'Failed to submit quest');
    }
    return json.data;
};

export const cancelQuest = async (questRef: string): Promise<{ message: string }> => {
    const response = await fetch(`${API_BASE_URL}/quests/${encodeURIComponent(questRef)}/cancel`, {
        method: 'POST',
        headers: authHeaders(),
    });
    const json = await response.json();
    if (!response.ok) {
        throw new Error(json.error ?? 'Failed to cancel quest');
    }
    return json.data;
};

export const fetchMyQuests = async (): Promise<{
    acceptances: QuestAcceptance[];
    rank_progress: RankProgress;
}> => {
    const response = await fetch(`${API_BASE_URL}/quests/my-quests`, {
        headers: authHeaders(),
    });
    if (!response.ok) {
        if (response.status === 401) {
            return { acceptances: [], rank_progress: { current_rank: 'Iron', next_rank: 'Silver', completed_quests: 0, total_xp: 0, quests_needed: 3, xp_needed: 150, progress_percent: 0 } };
        }
        throw new Error('Failed to fetch your quests');
    }
    const json = await response.json();
    return json.data;
};
