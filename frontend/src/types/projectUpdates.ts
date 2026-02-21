export class ProjectUpdate {
  name!: string;
  version?: string;
  lastUpdated?: string;
  lastBuild?: string;
  gitCommit?: string;
  branch?: string;
  lastCommitMessage?: string;
  status!: 'active' | 'maintenance' | 'deprecated';
  deployment!: {
    production?: string;
    development?: string;
  };
  type!: 'apps' | 'game_apps' | 'frontpage';
  path!: string;
  daysSinceUpdate?: number;
  daysSinceBuild?: number;
  isRecent!: boolean;
  deploymentStatus!: 'production' | 'development_only' | 'not_deployed';
  updateUrgency!: 'today' | 'recent' | 'moderate' | 'stale' | 'unknown';
}

export class ProjectUpdateStats {
  total_projects!: number;
  recent_updates!: number;
  production_deployments!: number;
  development_only!: number;
  not_deployed!: number;
  by_type!: Record<string, number>;
}

export class ProjectUpdateResponse {
  success!: boolean;
  data!: ProjectUpdate[];
  timestamp!: string;
  error?: string;
}

export class ProjectUpdateStatsResponse {
  success!: boolean;
  data!: ProjectUpdateStats;
  timestamp!: string;
  error?: string;
}

export class ProjectUpdateAttentionResponse {
  success!: boolean;
  data!: ProjectUpdate[];
  count!: number;
  timestamp!: string;
  error?: string;
}
