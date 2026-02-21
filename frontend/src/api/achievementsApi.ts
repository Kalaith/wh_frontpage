import api from './api';

export interface Achievement {
    slug: string;
    name: string;
    description: string;
    icon: string;
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    earnedBy: number;
}

export const achievementsApi = {
    fetchAll: async (): Promise<Achievement[]> => {
        try {
            const response = await api.get('/achievements');
            if (response.success && response.data) {
                return response.data;
            }
            return [];
        } catch (error) {
            console.error('Error fetching achievements:', error);
            return [];
        }
    }
};

export default achievementsApi;
