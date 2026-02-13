export interface QuestChainStep {
    title: string;
    description: string;
    xp: number;
}

export interface QuestChain {
    id: number;
    slug: string;
    name: string;
    description: string;
    steps: QuestChainStep[];
    total_steps: number;
    reward_xp: number;
    reward_badge_slug?: string;
    reward_title?: string;
    is_active: boolean;
}
