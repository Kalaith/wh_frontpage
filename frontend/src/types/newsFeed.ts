export class NewsItem {
  id!: string;
  type!: 'code_update' | 'deployment' | 'status_change';
  projectName!: string;
  message!: string;
  timestamp!: string;
  projectType!: string;
  deploymentStatus!: string;
  urgency!: string;
  metadata!: {
    gitCommit?: string;
    branch?: string;
    environments?: string[];
  };
}

export class ActivityStats {
  total_activity_items!: number;
  recent_activity_items!: number;
  activity_by_type!: Record<string, number>;
  activity_by_project!: Record<string, number>;
  most_active_projects!: Record<string, number>;
}

export class NewsFeedResponse {
  success!: boolean;
  data!: NewsItem[];
  count!: number;
  timestamp!: string;
  error?: string;
}

export class ActivityStatsResponse {
  success!: boolean;
  data!: ActivityStats;
  timestamp!: string;
  error?: string;
}
