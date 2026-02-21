export class HealthCheck {
  name!: string;
  status!: 'healthy' | 'warning' | 'critical' | 'info';
  message!: string;
}

export class HealthIssue {
  severity!: 'critical' | 'warning' | 'info';
  code!: string;
  message!: string;
  project!: string;
}

export class ProjectHealth {
  project_name!: string;
  status!: 'healthy' | 'warning' | 'critical';
  score!: number;
  issues!: HealthIssue[];
  checks!: HealthCheck[];
  last_checked!: string;
}

export class HealthRecommendation {
  priority!: 'high' | 'medium' | 'low';
  action!: string;
  details!: string;
}

export class SystemHealth {
  overall_status!: 'healthy' | 'warning' | 'critical' | 'unknown';
  total_projects!: number;
  healthy_projects!: number;
  warning_projects!: number;
  critical_projects!: number;
  issues!: HealthIssue[];
  recommendations!: HealthRecommendation[];
  project_health!: ProjectHealth[];
}

export class HealthSummary {
  overall_status!: 'healthy' | 'warning' | 'critical' | 'unknown';
  total_projects!: number;
  healthy_projects!: number;
  warning_projects!: number;
  critical_projects!: number;
  top_issues!: HealthIssue[];
  urgent_recommendations!: HealthRecommendation[];
}

export class HealthResponse {
  success!: boolean;
  data!: SystemHealth;
  timestamp!: string;
  error?: string;
}

export class HealthSummaryResponse {
  success!: boolean;
  data!: HealthSummary;
  timestamp!: string;
  error?: string;
}

export class CriticalProjectsResponse {
  success!: boolean;
  data!: ProjectHealth[];
  count!: number;
  timestamp!: string;
  error?: string;
}
