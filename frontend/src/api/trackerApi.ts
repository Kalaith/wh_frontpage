import api from './api';

export interface FeatureRequest {
  id?: number;
  title: string;
  description: string;
  category: string;
  priority: string;
  status: string;
  tags?: string[];
  votes: number;
  submitted_by?: string;
  created_at: string;
  updated_at: string;
  project?: {
    id: number;
    title: string;
    group_name?: string;
  };
}

export interface ProjectSuggestion {
  id?: number;
  name: string;
  description: string;
  suggested_group: string;
  rationale: string;
  votes: number;
  status: string;
  submitted_by?: string;
  created_at: string;
  updated_at: string;
}

export interface ActivityItem {
  id: number;
  type: string;
  action: string;
  title: string;
  description?: string;
  reference_id?: number;
  reference_type?: string;
  user?: string;
  created_at: string;
  created_at_relative: string;
}

export interface TrackerStats {
  projects: {
    total: number;
  };
  feature_requests: {
    total: number;
    open: number;
    in_progress: number;
    completed: number;
    closed: number;
  };
  suggestions: {
    total: number;
    suggested: number;
    under_review: number;
    approved: number;
    rejected: number;
  };
}

import type { ApiResponse } from '../types/common';

// Helper function to extract error message from ApiResponse
const getErrorMessage = (error: ApiResponse['error'], defaultMessage: string): string => {
  if (typeof error === 'string') return error;
  return error?.message ?? defaultMessage;
};

// Tracker API functions
export const trackerApi = {
  // Get tracker statistics
  async getStats(): Promise<TrackerStats> {
    const result = await api.request<TrackerStats>('/tracker/stats');
    
    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to fetch tracker stats'));
    }
    
    return result.data as TrackerStats;
  },

  // Get feature requests with optional filtering
  async getFeatureRequests(params?: {
    status?: string;
    priority?: string;
    category?: string;
    project_id?: number;
    sort_by?: string;
    sort_direction?: string;
    limit?: number;
  }): Promise<FeatureRequest[]> {
    const queryParams = new URLSearchParams();

    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          queryParams.append(key, value.toString());
        }
      });
    }

    const url = `/tracker/feature-requests${queryParams.toString() ? `?${queryParams.toString()}` : ''}`;
    const result = await api.request<FeatureRequest[]>(url);

    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to fetch feature requests'));
    }

    return result.data as FeatureRequest[];
  },

  // Create a new feature request
  async createFeatureRequest(data: {
    title: string;
    description: string;
    category?: string;
    priority?: string;
    tags?: string;
    submitted_by?: string;
  }): Promise<FeatureRequest> {
    const result = await api.request<FeatureRequest>('/tracker/feature-requests', {
      method: 'POST',
      body: JSON.stringify(data)
    });

    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to create feature request'));
    }

    return result.data as FeatureRequest;
  },

  // Get project suggestions with optional filtering
  async getProjectSuggestions(params?: {
    group?: string;
    status?: string;
    sort_by?: string;
    sort_direction?: string;
    limit?: number;
  }): Promise<ProjectSuggestion[]> {
    const queryParams = new URLSearchParams();

    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          queryParams.append(key, value.toString());
        }
      });
    }

    const url = `/tracker/project-suggestions${queryParams.toString() ? `?${queryParams.toString()}` : ''}`;
    const result = await api.request<ProjectSuggestion[]>(url);

    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to fetch project suggestions'));
    }

    return result.data as ProjectSuggestion[];
  },

  // Create a new project suggestion
  async createProjectSuggestion(data: {
    name: string;
    description: string;
    group?: string;
    rationale: string;
    submitted_by?: string;
  }): Promise<ProjectSuggestion> {
    const result = await api.request<ProjectSuggestion>('/tracker/project-suggestions', {
      method: 'POST',
      body: JSON.stringify(data)
    });

    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to create project suggestion'));
    }

    return result.data as ProjectSuggestion;
  },

  // Get activity feed
  async getActivityFeed(limit?: number, projectId?: number): Promise<ActivityItem[]> {
    const params = new URLSearchParams();
    if (limit) params.append('limit', limit.toString());
    if (projectId) params.append('project_id', projectId.toString());

    const url = `/tracker/activity${params.toString() ? `?${params.toString()}` : ''}`;
    const result = await api.request<ActivityItem[]>(url);

    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to fetch activity feed'));
    }

    return result.data as ActivityItem[];
  },

  // Vote on an item
  async vote(data: {
    item_type: 'feature_request' | 'project_suggestion';
    item_id: number;
    vote_value: 1 | -1;
  }): Promise<{ item_id: number; item_type: string; new_vote_count: number }> {
    const result = await api.request<{ item_id: number; item_type: string; new_vote_count: number }>('/tracker/vote', {
      method: 'POST',
      body: JSON.stringify(data)
    });

    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to record vote'));
    }

    return result.data as { item_id: number; item_type: string; new_vote_count: number };
  },

  // Get comments for a suggestion
  async getSuggestionComments(suggestionId: number): Promise<ProjectSuggestionComment[]> {
    const result = await api.request<ProjectSuggestionComment[]>(`/tracker/project-suggestions/${suggestionId}/comments`);
    
    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to fetch comments'));
    }
    
    return result.data as ProjectSuggestionComment[];
  },

  // Add a comment to a suggestion
  async addSuggestionComment(suggestionId: number, content: string, user?: { id: number; name: string }): Promise<ProjectSuggestionComment> {
    const result = await api.request<ProjectSuggestionComment>(`/tracker/project-suggestions/${suggestionId}/comments`, {
      method: 'POST',
      body: JSON.stringify({
        content,
        user_id: user?.id,
        user_name: user?.name
      })
    });

    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to add comment'));
    }

    return result.data as ProjectSuggestionComment;
  },

  // Publish a suggestion
  async publishSuggestion(suggestionId: number): Promise<void> {
    const result = await api.request(`/tracker/project-suggestions/${suggestionId}/publish`, {
      method: 'POST'
    });

    if (!result.success) {
      throw new Error(getErrorMessage(result.error, 'Failed to publish suggestion'));
    }
  }
};

export interface ProjectSuggestionComment {
  id: number;
  project_suggestion_id: number;
  user_id?: number;
  user_name: string;
  content: string;
  created_at: string;
  updated_at: string;
}