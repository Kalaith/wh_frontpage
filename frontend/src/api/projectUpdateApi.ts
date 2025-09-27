import api from './api';
import type { ProjectUpdateResponse, ProjectUpdateStatsResponse, ProjectUpdateAttentionResponse } from '../types/projectUpdates';
import type { ApiResponse } from '../types/common';

export class ProjectUpdateApi {
  /**
   * Get all project updates with status information
   */
  static async getAllUpdates(): Promise<ApiResponse<ProjectUpdateResponse>> {
    return api.request<ProjectUpdateResponse>('/projects/updates');
  }

  /**
   * Get recent project updates (last 7 days)
   */
  static async getRecentUpdates(): Promise<ApiResponse<ProjectUpdateResponse>> {
    return api.request<ProjectUpdateResponse>('/projects/updates/recent');
  }

  /**
   * Get project update statistics
   */
  static async getStatistics(): Promise<ApiResponse<ProjectUpdateStatsResponse>> {
    return api.request<ProjectUpdateStatsResponse>('/projects/updates/stats');
  }

  /**
   * Get projects that need attention
   */
  static async getProjectsNeedingAttention(): Promise<ApiResponse<ProjectUpdateAttentionResponse>> {
    return api.request<ProjectUpdateAttentionResponse>('/projects/updates/attention');
  }
}