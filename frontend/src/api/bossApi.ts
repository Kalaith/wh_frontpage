import { Boss } from '../types/Boss';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

export const fetchCurrentBoss = async (): Promise<Boss> => {
    const response = await fetch(`${API_BASE_URL}/bosses/current`);
    if (!response.ok) {
        throw new Error('Failed to fetch active boss');
    }
    const json = await response.json();
    return json.data;
};
