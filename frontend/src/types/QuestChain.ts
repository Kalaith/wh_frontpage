export type QuestRank = 'Iron' | 'Silver' | 'Gold' | 'Jade' | 'Diamond';

export interface RSBrief {
  context?: string;
  constraints?: string;
  technical_notes?: string[];
  suggested_prompt?: string;
}

export interface QuestChainStep {
  id?: string;
  title: string;
  description: string;
  xp: number;
  type?: 'Quest' | 'Boss' | 'Raid' | string;
  rank_required?: QuestRank;
  quest_level?: number;
  goal?: string;
  player_steps?: string[];
  done_when?: string[];
  class_fantasy?: string;
  class?: string;
  difficulty?: number;
  labels?: string[];
  specific?: string;
  metric_baseline?: string;
  metric_target?: string;
  in_scope?: string[];
  out_of_scope?: string[];
  risk_level?: 'low' | 'med' | 'high' | string;
  rollback_plan?: string;
  due_date?: string;
  proof_required?: string[];
  rs_brief?: RSBrief;
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
  season_id?: number | null;
  is_active: boolean;
  type?: string;
  labels?: string[];
  entry_criteria?: string[];
  go_no_go_gates?: string[];
  rank_required?: QuestRank;
}
