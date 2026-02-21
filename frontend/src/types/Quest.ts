export interface QuestLabel {
  name: string;
  color: string;
}

export interface Quest {
  id: number;
  number: number;
  quest_code?: string;
  title: string;
  url: string;
  body: string;
  difficulty: number;
  class: string;
  xp: number;
  labels: QuestLabel[];
  class_fantasy?: string;
  rank_required?: 'Iron' | 'Silver' | 'Gold' | 'Jade' | 'Diamond' | string;
  quest_level?: number;
  dependency_type?: 'Independent' | 'Chained' | 'Blocked' | string;
  depends_on?: string[];
  unlock_condition?: string;
  goal?: string;
  player_steps?: string[];
  done_when?: string[];
  rs_brief?: {
    context?: string;
    constraints?: string;
    technical_notes?: string[];
    suggested_prompt?: string;
  };
  specific?: string;
  metric_baseline?: string;
  metric_target?: string;
  in_scope?: string[];
  out_of_scope?: string[];
  risk_level?: 'low' | 'med' | 'high' | string;
  rollback_plan?: string;
  due_date?: string;
  proof_required?: string[];
}

export interface QuestAcceptance {
  id: number;
  quest_ref: string;
  status: 'accepted' | 'submitted' | 'completed' | 'rejected';
  accepted_at: string;
  submitted_at?: string;
  completed_at?: string;
}

export interface RankProgress {
  current_rank: string;
  next_rank: string | null;
  completed_quests: number;
  total_xp: number;
  quests_needed: number;
  xp_needed: number;
  progress_percent: number;
}
