export interface Badge {
    id: number;
    badge_slug: string;
    badge_name: string;
    earned_at: string;
}

export interface Mastery {
    project_id: number;
    project_title: string;
    project_path: string;
    mastery_level: number;
    contributions: number;
    reviews: number;
}

export interface Adventurer {
    id: number;
    github_username: string;
    class: string;
    level: number;
    xp_total: number;
    spec_primary?: string;
    spec_secondary?: string;
    equipped_title?: string;
    glow_streak: number;
    created_at: string;
    badges?: Badge[];
    mastery?: Mastery[];
}
