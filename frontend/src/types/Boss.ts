export interface Boss {
    id: number;
    name: string;
    description?: string;
    github_issue_url?: string;
    threat_level: number;
    hp_total: number;
    hp_current: number;
    status: 'active' | 'stabilizing' | 'defeated';
    project_id?: number;
    season_id?: number;
    created_at: string;
    defeated_at?: string;
}
