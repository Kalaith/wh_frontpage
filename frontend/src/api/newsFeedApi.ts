import api from './api';
import type {
  NewsFeedResponse,
  ActivityStatsResponse,
} from '../types/newsFeed';
import type { ApiResponse } from '../types/common';

export class NewsFeedApi {
  /**
   * Get the main news feed
   */
  static async getNewsFeed(
    limit: number = 20
  ): Promise<ApiResponse<NewsFeedResponse>> {
    return api.request<NewsFeedResponse>(`/news?limit=${limit}`);
  }

  /**
   * Get recent activity
   */
  static async getRecentActivity(
    days: number = 7
  ): Promise<ApiResponse<NewsFeedResponse>> {
    return api.request<NewsFeedResponse>(`/news/recent?days=${days}`);
  }

  /**
   * Get activity statistics
   */
  static async getActivityStats(): Promise<ApiResponse<ActivityStatsResponse>> {
    return api.request<ActivityStatsResponse>('/news/stats');
  }

  /**
   * Get changelog for a specific project
   */
  static async getProjectChangelog(
    projectName: string,
    limit: number = 10
  ): Promise<ApiResponse<NewsFeedResponse>> {
    return api.request<NewsFeedResponse>(
      `/news/project/${encodeURIComponent(projectName)}?limit=${limit}`
    );
  }
}
