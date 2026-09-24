import api from './api';
import type {
  SystemHealth,
  HealthSummary,
  ProjectHealth,
  HealthRecommendation,
} from '../types/projectHealth';
import type { ApiResponse } from '../types/common';

export class ProjectHealthApi {
  /**
   * Get comprehensive system health report
   */
  static async getSystemHealth(): Promise<ApiResponse<SystemHealth>> {
    return api.request<SystemHealth>('/health/system');
  }

  /**
   * Get health summary for dashboard display
   */
  static async getHealthSummary(): Promise<ApiResponse<HealthSummary>> {
    return api.request<HealthSummary>('/health/summary');
  }

  /**
   * Get health status for a specific project
   */
  static async getProjectHealth(
    projectName: string
  ): Promise<ApiResponse<ProjectHealth>> {
    return api.request<ProjectHealth>(
      `/health/project/${encodeURIComponent(projectName)}`
    );
  }

  /**
   * Get projects with critical issues
   */
  static async getCriticalProjects(): Promise<ApiResponse<ProjectHealth[]>> {
    return api.request<ProjectHealth[]>('/health/critical');
  }

  /**
   * Get system recommendations
   */
  static async getRecommendations(): Promise<
    ApiResponse<HealthRecommendation[]>
  > {
    return api.request<HealthRecommendation[]>('/health/recommendations');
  }

  /**
   * Run health check on demand
   */
  static async runHealthCheck(): Promise<ApiResponse<SystemHealth>> {
    return api.request<SystemHealth>('/health/check', { method: 'POST' });
  }
}
