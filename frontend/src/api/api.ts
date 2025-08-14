// src/api/api.ts - API client for backend communication
import type { ProjectsData, Project } from '../types/projects';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';

export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  error?: {
    message: string;
    details?: string;
  };
  message?: string;
}

class ApiClient {
  private baseUrl: string;

  constructor(baseUrl: string = API_BASE_URL) {
    this.baseUrl = baseUrl;
  }

  private async request<T = any>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}${endpoint}`;
    
    const token = localStorage.getItem('jwt_token');
    const defaultHeaders: Record<string, string> = {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {})
    };

    try {
      const response = await fetch(url, {
        ...options,
        headers: {
          ...defaultHeaders,
          ...options.headers,
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('API request failed:', error);
      return {
        success: false,
        error: {
          message: error instanceof Error ? error.message : 'Unknown error occurred',
        },
      };
    }
  }

  // Health check
  async healthCheck(): Promise<ApiResponse> {
    return this.request('/health');
  }

  // Projects API
  async getProjects(): Promise<ApiResponse<ProjectsData>> {
    return this.request<ProjectsData>('/projects');
  }

  async getProjectsByGroup(group: string): Promise<ApiResponse<Project[]>> {
    return this.request<Project[]>(`/projects/${group}`);
  }

  async createProject(projectData: Partial<Project>): Promise<ApiResponse<Project>> {
    return this.request<Project>('/projects', {
      method: 'POST',
      body: JSON.stringify(projectData),
    });
  }

  async updateProject(projectId: number, projectData: Partial<Project>): Promise<ApiResponse<Project>> {
    return this.request<Project>(`/projects/${projectId}`, {
      method: 'PUT',
      body: JSON.stringify(projectData),
    });
  }

  async deleteProject(projectId: number): Promise<ApiResponse> {
    return this.request(`/projects/${projectId}`, {
      method: 'DELETE'
    });
  }

  // Auth via frontpage proxy -> auth app
  async login(email: string, password: string): Promise<ApiResponse<{ user: any; token: string }>> {
    const res = await this.request(`/auth/login`, {
      method: 'POST',
      body: JSON.stringify({ email, password })
    });
    if (res.success && res.data?.token) {
      localStorage.setItem('jwt_token', res.data.token);
    }
    return res as ApiResponse<{ user: any; token: string }>;
  }

  async register(userData: Record<string, any>): Promise<ApiResponse<{ user: any; token: string }>> {
    const res = await this.request(`/auth/register`, {
      method: 'POST',
      body: JSON.stringify(userData)
    });
    if (res.success && res.data?.token) {
      localStorage.setItem('jwt_token', res.data.token);
    }
    return res as ApiResponse<{ user: any; token: string }>;
  }
}

// Create and export a singleton instance
const api = new ApiClient();
export default api;
