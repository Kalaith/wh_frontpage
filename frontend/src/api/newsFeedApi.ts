import api from './api';
import type { NewsFeedResponse, ActivityStatsResponse } from '../types/newsFeed';

export class NewsFeedApi {
  /**
   * Get the main news feed
   */
  static async getNewsFeed(limit: number = 20): Promise<NewsFeedResponse> {
    return api.get(`/news?limit=${limit}`);
  }

  /**
   * Get recent activity
   */
  static async getRecentActivity(days: number = 7): Promise<NewsFeedResponse> {
    return api.get(`/news/recent?days=${days}`);
  }

  /**
   * Get activity statistics
   */
  static async getActivityStats(): Promise<ActivityStatsResponse> {
    return api.get('/news/stats');
  }

  /**
   * Get changelog for a specific project
   */
  static async getProjectChangelog(projectName: string, limit: number = 10): Promise<NewsFeedResponse> {
    return api.get(`/news/project/${encodeURIComponent(projectName)}?limit=${limit}`);
  }
}