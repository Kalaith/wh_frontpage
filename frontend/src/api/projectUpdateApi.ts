import api from './api';
import type { ProjectUpdateResponse, ProjectUpdateStatsResponse, ProjectUpdateAttentionResponse } from '../types/projectUpdates';

export class ProjectUpdateApi {
  /**
   * Get all project updates with status information
   */
  static async getAllUpdates(): Promise<ProjectUpdateResponse> {
    return api.get('/projects/updates');
  }

  /**
   * Get recent project updates (last 7 days)
   */
  static async getRecentUpdates(): Promise<ProjectUpdateResponse> {
    return api.get('/projects/updates/recent');
  }

  /**
   * Get project update statistics
   */
  static async getStatistics(): Promise<ProjectUpdateStatsResponse> {
    return api.get('/projects/updates/stats');
  }

  /**
   * Get projects that need attention
   */
  static async getProjectsNeedingAttention(): Promise<ProjectUpdateAttentionResponse> {
    return api.get('/projects/updates/attention');
  }
}