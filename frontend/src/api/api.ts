// src/api/api.ts - API client for backend communication
import type { ProjectsData, Project } from '../types/projects';

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
const DEFAULT_TIMEOUT_MS = 10_000; // 10s

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
    options: RequestInit = {},
    timeoutMs: number = DEFAULT_TIMEOUT_MS
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}${endpoint}`;

    const token = localStorage.getItem('token');
    const defaultHeaders: Record<string, string> = {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };

    const controller = new AbortController();
    const signal = controller.signal;

    const timer = setTimeout(() => controller.abort(), timeoutMs);

    try {
      const response = await fetch(url, {
        ...options,
        signal,
        headers: {
          ...defaultHeaders,
          ...options.headers,
        },
      });

      clearTimeout(timer);

      if (!response.ok) {
        // try to parse error body if possible
        let errBody: any = null;
        try {
          errBody = await response.json();
        } catch (_) {
          /* ignore */
        }
        return {
          success: false,
          error: {
            message:
              errBody?.error?.message ||
              `HTTP ${response.status}: ${response.statusText}`,
            details: errBody,
          },
        };
      }

      // safe parse JSON, tolerate empty body
      const text = await response.text();
      const data = text ? JSON.parse(text) : { success: true, data: null };
      return data as ApiResponse<T>;
    } catch (error: any) {
      clearTimeout(timer);
      const message =
        error?.name === 'AbortError'
          ? 'Request timed out'
          : error instanceof Error
            ? error.message
            : 'Unknown error';
      console.error('API request failed:', message, error);
      return {
        success: false,
        error: {
          message,
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

  async createProject(
    projectData: Partial<Project>
  ): Promise<ApiResponse<Project>> {
    return this.request<Project>('/projects', {
      method: 'POST',
      body: JSON.stringify(projectData),
    });
  }

  async updateProject(
    projectId: number,
    projectData: Partial<Project>
  ): Promise<ApiResponse<Project>> {
    return this.request<Project>(`/projects/${projectId}`, {
      method: 'PUT',
      body: JSON.stringify(projectData),
    });
  }

  async deleteProject(projectId: number): Promise<ApiResponse> {
    return this.request(`/projects/${projectId}`, {
      method: 'DELETE',
    });
  }

  // Auth via frontpage proxy -> auth app
  async login(
    email: string,
    password: string
  ): Promise<ApiResponse<{ user: any; token: string }>> {
    const res = await this.request(`/auth/login`, {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    if (res.success && res.data?.token) {
      localStorage.setItem('token', res.data.token);
    }
    return res as ApiResponse<{ user: any; token: string }>;
  }

  async register(
    userData: Record<string, any>
  ): Promise<ApiResponse<{ user: any; token: string }>> {
    const res = await this.request(`/auth/register`, {
      method: 'POST',
      body: JSON.stringify(userData),
    });
    if (res.success && res.data?.token) {
      localStorage.setItem('token', res.data.token);
    }
    return res as ApiResponse<{ user: any; token: string }>;
  }
}

// Create and export a singleton instance
const api = new ApiClient();
export default api;
