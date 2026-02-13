import { Adventurer } from '../types/Adventurer';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

export const fetchLeaderboard = async (): Promise<Adventurer[]> => {
    const response = await fetch(`${API_BASE_URL}/leaderboard`);
    if (!response.ok) {
        throw new Error('Failed to fetch leaderboard');
    }
    const json = await response.json();
    return json.data || [];
};
