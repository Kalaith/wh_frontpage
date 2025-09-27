import api from './api';
import type { HealthResponse, HealthSummaryResponse, CriticalProjectsResponse } from '../types/projectHealth';
import type { ApiResponse } from '../types/common';

export class ProjectHealthApi {
  /**
   * Get comprehensive system health report
   */
  static async getSystemHealth(): Promise<ApiResponse<HealthResponse>> {
    return api.request<HealthResponse>('/health/system');
  }

  /**
   * Get health summary for dashboard display
   */
  static async getHealthSummary(): Promise<ApiResponse<HealthSummaryResponse>> {
    return api.request<HealthSummaryResponse>('/health/summary');
  }

  /**
   * Get health status for a specific project
   */
  static async getProjectHealth(projectName: string): Promise<ApiResponse<HealthResponse>> {
    return api.request<HealthResponse>(`/health/project/${encodeURIComponent(projectName)}`);
  }

  /**
   * Get projects with critical issues
   */
  static async getCriticalProjects(): Promise<ApiResponse<CriticalProjectsResponse>> {
    return api.request<CriticalProjectsResponse>('/health/critical');
  }

  /**
   * Get system recommendations
   */
  static async getRecommendations(): Promise<ApiResponse<HealthResponse>> {
    return api.request<HealthResponse>('/health/recommendations');
  }

  /**
   * Run health check on demand
   */
  static async runHealthCheck(): Promise<ApiResponse<HealthResponse>> {
    return api.request<HealthResponse>('/health/check', { method: 'POST' });
  }
}