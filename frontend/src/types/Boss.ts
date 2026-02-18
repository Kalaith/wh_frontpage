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
    labels?: string[];
    threat_type?: string;
    deadline?: string;
    risk_level?: 'low' | 'med' | 'high' | string;
    rollback_plan?: string;
    kill_criteria?: string[];
    hp_tasks?: string[];
    proof_required?: string[];
}
