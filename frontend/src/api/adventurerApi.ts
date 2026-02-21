import { Adventurer } from '../types/Adventurer';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api';

export const fetchAdventurer = async (username: string): Promise<Adventurer> => {
    const response = await fetch(`${API_BASE_URL}/adventurers/${username}`);
    if (!response.ok) {
        if (response.status === 404) {
            throw new Error('Adventurer not found');
        }
        throw new Error('Failed to fetch adventurer profile');
    }
    const json = await response.json();
    return json.data;
};
