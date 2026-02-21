import api from './api';

export interface WeeklyHeist {
    id: number;
    goal: string;
    target: number;
    current: number;
    participants: number;
    reward: string;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
    created_at: string;
}

export const heistApi = {
    fetchCurrentHeist: async (): Promise<WeeklyHeist | null> => {
        try {
            const response = await api.get('/heist/current');
            if (response.success && response.data) {
                return response.data;
            }
            return null;
        } catch (error) {
            console.error('Error fetching current heist:', error);
            return null;
        }
    }
};

export default heistApi;
