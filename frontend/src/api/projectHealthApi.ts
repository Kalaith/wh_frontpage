import api from './api';
import type { HealthResponse, HealthSummaryResponse } from '../types/projectHealth';

export class ProjectHealthApi {
  /**
   * Get comprehensive system health report
   */
  static async getSystemHealth(): Promise<HealthResponse> {
    return api.get('/health/system');
  }

  /**
   * Get health summary for dashboard display
   */
  static async getHealthSummary(): Promise<HealthSummaryResponse> {
    return api.get('/health/summary');
  }

  /**
   * Get health status for a specific project
   */
  static async getProjectHealth(projectName: string): Promise<HealthResponse> {
    return api.get(`/health/project/${encodeURIComponent(projectName)}`);
  }

  /**
   * Get projects with critical issues
   */
  static async getCriticalProjects(): Promise<HealthResponse> {
    return api.get('/health/critical');
  }

  /**
   * Get system recommendations
   */
  static async getRecommendations(): Promise<HealthResponse> {
    return api.get('/health/recommendations');
  }

  /**
   * Run health check on demand
   */
  static async runHealthCheck(): Promise<HealthResponse> {
    return api.post('/health/check', {});
  }
}