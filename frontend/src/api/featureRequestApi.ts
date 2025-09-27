import { 
  FeatureRequest, 
  CreateFeatureRequest, 
  CastVote, 
  User, 
  UserDashboard,
  AdminStats,
  EggTransaction,
  Vote
} from '../types/featureRequest';
import type { ApiResponse } from '../types/common';

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? '/api';

class FeatureRequestApiError extends Error {
  constructor(public status: number, message: string, public response?: unknown) {
    super(message);
    this.name = 'FeatureRequestApiError';
  }
}

// Store Auth0 token getter function
let getAuth0Token: (() => Promise<string>) | null = null;

export function setAuth0TokenGetter(tokenGetter: () => Promise<string>) {
  getAuth0Token = tokenGetter;
}

async function apiRequest<T>(
  endpoint: string, 
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  let token: string | null = null;
  
  // Try to get Auth0 token first
  if (getAuth0Token) {
    try {
      token = await getAuth0Token();
    } catch (error) {
      console.warn('Failed to get Auth0 token, falling back to localStorage:', error);
    }
  }
  
  // No fallback needed - Auth0 only
  
  const config: RequestInit = {
    headers: {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    },
    ...options,
  };

  try {
    const response = await fetch(`${API_BASE}${endpoint}`, config);
    const data = await response.json();
    
    if (!response.ok) {
      throw new FeatureRequestApiError(response.status, data.message ?? 'Request failed', data);
    }
    
    return data;
  } catch (error) {
    if (error instanceof FeatureRequestApiError) {
      throw error;
    }
    throw new FeatureRequestApiError(0, 'Network error occurred');
  }
}

export const featureRequestApi = {
  // Feature Requests
  async getAllFeatures(params?: {
    status?: string;
    project_id?: number;
    category?: string;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    limit?: number;
    search?: string;
  }): Promise<FeatureRequest[]> {
    const queryParams = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          queryParams.append(key, String(value));
        }
      });
    }
    
    const response = await apiRequest<FeatureRequest[]>(`/features?${queryParams}`);
    return response.data ?? [];
  },

  async getFeatureById(id: number): Promise<FeatureRequest> {
    const response = await apiRequest<FeatureRequest>(`/features/${id}`);
    return response.data as FeatureRequest;
  },

  async createFeature(data: CreateFeatureRequest & { user_id: number }): Promise<FeatureRequest> {
    const response = await apiRequest<FeatureRequest>('/features', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data as FeatureRequest;
  },

  async voteOnFeature(data: CastVote): Promise<{ success: boolean; message: string }> {
    const response = await apiRequest<{ success: boolean; message: string }>('/features/vote', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data as { success: boolean; message: string };
  },

  async getUserFeatures(userId: number): Promise<FeatureRequest[]> {
    const response = await apiRequest<FeatureRequest[]>(`/users/${userId}/features`);
    return response.data ?? [];
  },

  async getUserVotes(userId: number): Promise<Vote[]> {
    const response = await apiRequest<Vote[]>(`/users/${userId}/votes`);
    return response.data ?? [];
  },

  async getStats(): Promise<{ total: number; approved: number; pending: number; completed: number }> {
    const response = await apiRequest<{ total: number; approved: number; pending: number; completed: number }>('/features/stats');
    return response.data as { total: number; approved: number; pending: number; completed: number };
  },

  // User Management
  async register(data: {
    username: string;
    email: string;
    password: string;
    display_name?: string;
  }): Promise<User> {
    const response = await apiRequest<User>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data as User;
  },

  async login(data: {
    email: string;
    password: string;
  }): Promise<{ user: User; token: string; expires_at: string }> {
    const response = await apiRequest<{ user: User; token: string; expires_at: string }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify(data),
    });
    return response.data as { user: User; token: string; expires_at: string };
  },

  async getProfile(): Promise<User> {
    const response = await apiRequest<User>('/user/profile');
    return response.data as User;
  },

  async updateProfile(data: {
    display_name?: string;
    username?: string;
  }): Promise<User> {
    const response = await apiRequest<User>('/user/profile', {
      method: 'PUT',
      body: JSON.stringify(data),
    });
    return response.data as User;
  },

  async claimDailyEggs(): Promise<{
    eggs_earned: number;
    new_balance: number;
    can_claim_tomorrow: boolean;
  }> {
    const response = await apiRequest<{
      eggs_earned: number;
      new_balance: number;
      can_claim_tomorrow: boolean;
    }>('/user/claim-daily-eggs', {
      method: 'POST',
    });
    return response.data as {
      eggs_earned: number;
      new_balance: number;
      can_claim_tomorrow: boolean;
    };
  },

  async getTransactions(params?: {
    limit?: number;
    type?: string;
  }): Promise<{
    transactions: EggTransaction[];
    stats: {
      total_eggs_earned: number;
      total_eggs_spent: number;
      feature_requests_count: number;
      votes_count: number;
    };
  }> {
    const queryParams = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          queryParams.append(key, String(value));
        }
      });
    }
    
    const response = await apiRequest<{
      transactions: EggTransaction[];
      stats: {
        total_eggs_earned: number;
        total_eggs_spent: number;
        feature_requests_count: number;
        votes_count: number;
      };
    }>(`/user/transactions?${queryParams}`);
    return response.data as {
      transactions: EggTransaction[];
      stats: {
        total_eggs_earned: number;
        total_eggs_spent: number;
        feature_requests_count: number;
        votes_count: number;
      };
    };
  },

  async getUserDashboard(): Promise<UserDashboard> {
    const response = await apiRequest<UserDashboard>('/user/dashboard');
    return response.data as UserDashboard;
  },

  // Admin Functions
  async getPendingFeatures(params?: {
    limit?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
  }): Promise<FeatureRequest[]> {
    const queryParams = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          queryParams.append(key, String(value));
        }
      });
    }
    
    const response = await apiRequest<FeatureRequest[]>(`/admin/features/pending?${queryParams}`);
    return response.data ?? [];
  },

  async approveFeature(id: number, notes?: string): Promise<FeatureRequest> {
    const response = await apiRequest<FeatureRequest>(`/admin/features/${id}/approve`, {
      method: 'POST',
      body: JSON.stringify({ notes }),
    });
    return response.data as FeatureRequest;
  },

  async rejectFeature(id: number, notes?: string): Promise<FeatureRequest> {
    const response = await apiRequest<FeatureRequest>(`/admin/features/${id}/reject`, {
      method: 'POST',
      body: JSON.stringify({ notes }),
    });
    return response.data as FeatureRequest;
  },

  async updateFeatureStatus(id: number, status: string, notes?: string): Promise<FeatureRequest> {
    const response = await apiRequest<FeatureRequest>(`/admin/features/${id}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status, approval_notes: notes }),
    });
    return response.data as FeatureRequest;
  },

  async bulkApproveFeatures(featureIds: number[], notes?: string): Promise<{
    approved_count: number;
    total_requested: number;
    errors: string[];
  }> {
    const response = await apiRequest<{
      approved_count: number;
      total_requested: number;
      errors: string[];
    }>('/admin/features/bulk-approve', {
      method: 'POST',
      body: JSON.stringify({ feature_ids: featureIds, notes }),
    });
    return response.data as {
      approved_count: number;
      total_requested: number;
      errors: string[];
    };
  },

  async adjustUserEggs(userId: number, amount: number, reason?: string): Promise<{
    user_id: number;
    adjustment_amount: number;
    new_balance: number;
    reason: string;
    adjusted_by: string;
  }> {
    const response = await apiRequest<{
      user_id: number;
      adjustment_amount: number;
      new_balance: number;
      reason: string;
      adjusted_by: string;
    }>(`/admin/users/${userId}/eggs`, {
      method: 'POST',
      body: JSON.stringify({ amount, reason }),
    });
    return response.data as {
      user_id: number;
      adjustment_amount: number;
      new_balance: number;
      reason: string;
      adjusted_by: string;
    };
  },

  async getAdminStats(): Promise<AdminStats> {
    const response = await apiRequest<AdminStats>('/admin/stats');
    return response.data as AdminStats;
  },

  async getUserManagement(params?: {
    limit?: number;
    search?: string;
    role?: string;
  }): Promise<User[]> {
    const queryParams = new URLSearchParams();
    if (params) {
      Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null) {
          queryParams.append(key, String(value));
        }
      });
    }
    
    const response = await apiRequest<User[]>(`/admin/users?${queryParams}`);
    return response.data ?? [];
  },

  async deleteAccount(): Promise<{ success: boolean; message: string }> {
    const response = await apiRequest<{ success: boolean; message: string }>('/user/delete-account', {
      method: 'DELETE',
    });
    return response.data as { success: boolean; message: string };
  },
};

export { FeatureRequestApiError };