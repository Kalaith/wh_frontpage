// src/api/api.ts - API client for backend communication
import type { ProjectsData, Project } from '../types/projects';
import type { AuthUser } from '../entities/Auth';
import type { ApiResponse } from '../types/common';
import { createAuthError, createServerError } from '../utils/errorHandling';

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ?? '/api';
const DEFAULT_TIMEOUT_MS = 10_000; // 10s

class ApiClient {
  private baseUrl: string;

  constructor(baseUrl: string = API_BASE_URL) {
    this.baseUrl = baseUrl;
  }

  public async request<T = unknown>(
    endpoint: string,
    options: RequestInit = {},
    timeoutMs: number = DEFAULT_TIMEOUT_MS
  ): Promise<ApiResponse<T>> {
    const url = `${this.baseUrl}${endpoint}`;

    // Get token from auth-storage (Zustand persist)
    let token = null;
    const authStorage = localStorage.getItem('auth-storage');
    if (authStorage) {
      try {
        const { state } = JSON.parse(authStorage);
        if (state && state.token) {
          token = state.token;
        }
      } catch (e) {
        console.error('Failed to parse auth-storage', e);
      }
    }

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
        let errBody: { message?: string; error?: { message?: string } } | null = null;
        try {
          errBody = await response.json();
        } catch {
          /* ignore */
        }

        // Handle authentication errors specially
        if (response.status === 401) {
          console.warn('Authentication failed - token may be invalid or expired');
          const authError = createAuthError('Authentication required. Please log in again.');
          return {
            success: false,
            error: {
              ...authError,
              details: JSON.stringify(errBody),
            },
          };
        }

        // Handle server errors
        if (response.status >= 500) {
          console.error('Server error:', response.status, errBody);
          const serverError = createServerError(
            response.status === 500
              ? 'Server error occurred. Please try again later.'
              : `Server error (${response.status}): ${response.statusText}`
          );
          return {
            success: false,
            error: {
              ...serverError,
              details: JSON.stringify(errBody),
            },
          };
        }

        return {
          success: false,
          error: {
            message:
              errBody?.error?.message ??
              errBody?.message ??
              `HTTP ${response.status}: ${response.statusText}`,
            details: JSON.stringify(errBody),
            status: response.status,
          },
        };
      }

      // safe parse JSON, tolerate empty body
      const text = await response.text();
      const data = text ? JSON.parse(text) : { success: true, data: null };
      return data as ApiResponse<T>;
    } catch (error: unknown) {
      clearTimeout(timer);
      const message =
        (error as { name?: string })?.name === 'AbortError'
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

  async getHomepageProjects(): Promise<ApiResponse<ProjectsData>> {
    return this.request<ProjectsData>('/projects/homepage');
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
  ): Promise<ApiResponse<{ user: AuthUser; token: string }>> {
    const res = await this.request<{ user: AuthUser; token: string }>(`/auth/login`, {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    return res as ApiResponse<{ user: AuthUser; token: string }>;
  }

  async register(
    userData: Record<string, unknown>
  ): Promise<ApiResponse<{ user: AuthUser; token: string }>> {
    const res = await this.request<{ user: AuthUser; token: string }>(`/auth/register`, {
      method: 'POST',
      body: JSON.stringify(userData),
    });
    return res as ApiResponse<{ user: AuthUser; token: string }>;
  }

  async getCurrentUser(): Promise<ApiResponse<AuthUser>> {
    return this.request<AuthUser>('/auth/user');
  }
}

// Create and export a singleton instance
const api = new ApiClient();
export default api;
