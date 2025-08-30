export interface FeatureRequest {
  id: number;
  title: string;
  description: string;
  category?: string;
  use_case?: string;
  expected_benefits?: string;
  priority_level: 'low' | 'medium' | 'high';
  feature_type: 'enhancement' | 'new_feature' | 'bug_fix' | 'ui_improvement' | 'performance';
  status: 'pending' | 'approved' | 'open' | 'planned' | 'in_progress' | 'completed' | 'rejected';
  approval_notes?: string;
  total_eggs: number;
  vote_count: number;
  tags?: string[];
  project_id?: number;
  approved_at?: string;
  created_at: string;
  updated_at: string;
  user?: {
    id: number;
    username: string;
    display_name: string;
  };
  project?: {
    id: number;
    title: string;
    group_name?: string;
  };
  approved_by?: {
    id: number;
    username: string;
    display_name: string;
  };
  votes?: Vote[];
}

export interface Vote {
  id: number;
  feature_id: number;
  eggs_allocated: number;
  created_at: string;
  user?: {
    username: string;
    display_name: string;
  };
  feature?: {
    id: number;
    title: string;
    status: string;
    total_eggs: number;
  };
}

export interface User {
  id: number;
  username: string;
  email: string;
  display_name: string;
  role: 'user' | 'admin';
  egg_balance: number;
  is_verified: boolean;
  can_claim_daily: boolean;
  member_since: string;
  stats?: {
    features_created: number;
    votes_cast: number;
    eggs_spent: number;
    eggs_earned: number;
    features_approved: number;
    features_completed: number;
  };
}

export interface EggTransaction {
  id: number;
  amount: number;
  type: 'earn' | 'spend' | 'vote' | 'daily_reward' | 'registration_bonus' | 'kofi_reward' | 'admin_adjustment';
  description: string;
  reference_id?: number;
  reference_type?: string;
  created_at: string;
}

export interface CreateFeatureRequest {
  title: string;
  description: string;
  category?: string;
  use_case?: string;
  expected_benefits?: string;
  priority_level?: 'low' | 'medium' | 'high';
  feature_type?: 'enhancement' | 'new_feature' | 'bug_fix' | 'ui_improvement' | 'performance';
  project_id?: number;
  tags?: string[];
}

export interface CastVote {
  user_id: number;
  feature_id: number;
  eggs_allocated: number;
}

export interface UserDashboard {
  user: User;
  my_features: FeatureRequest[];
  my_votes: Vote[];
  recent_transactions: EggTransaction[];
  popular_features: FeatureRequest[];
  stats: {
    total_features: number;
    approved_features: number;
    total_votes: number;
    eggs_invested: number;
  };
}

export interface AdminStats {
  users: {
    total: number;
    verified: number;
    admins: number;
    new_this_month: number;
  };
  features: {
    total: number;
    pending: number;
    approved: number;
    in_progress: number;
    completed: number;
    rejected: number;
  };
  eggs: {
    total_in_circulation: number;
    total_spent: number;
    total_earned: number;
    daily_rewards_claimed_today: number;
  };
  votes: {
    total_votes: number;
    total_eggs_allocated: number;
    unique_voters: number;
    most_voted_feature?: {
      id: number;
      title: string;
      total_eggs: number;
      vote_count: number;
      user: string;
    };
  };
  recent_activity: {
    new_features_today: number;
    votes_today: number;
    eggs_spent_today: number;
  };
}

export interface ApiResponse<T = unknown> {
  success: boolean;
  message?: string;
  data?: T;
  error?: string;
  count?: number;
}