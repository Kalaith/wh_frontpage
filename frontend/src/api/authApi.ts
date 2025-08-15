/**
 * Authentication API Client
 * Handles all authentication-related API requests
 */
import type { AuthUser, LoginRequest, RegisterRequest } from '../entities/Auth';

// Use the existing API base URL from the main API client
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/frontpage/api';

/**
 * Custom error handler to provide more descriptive error messages
 */
const handleApiError = (error: unknown): never => {
  // Log the error for debugging (only in development)
  if (import.meta.env.DEV) {
    console.error('Auth API Error:', error);
  }
  
  // Handle fetch errors
  if (error instanceof TypeError && error.message.includes('fetch')) {
    throw {
      code: 'CONNECTION_ERROR',
      message: 'Unable to connect to the server. Please check your connection or try again later.',
    };
  }
  
  // Handle other errors
  if (error && typeof error === 'object' && 'message' in error) {
    throw error;
  }
  
  throw {
    code: 'UNKNOWN_ERROR',
    message: 'An unexpected error occurred. Please try again.',
  };
};

/**
 * Make API request with proper error handling
 */
const getStoredToken = (): string | null => {
  return localStorage.getItem('token');
};

const apiRequest = async <T>(endpoint: string, options: RequestInit = {}): Promise<T> => {
  const token = getStoredToken();

  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
    ...options,
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw {
      code: response.status === 401 ? 'UNAUTHORIZED' : 'API_ERROR',
      message: errorData.error?.message || `HTTP ${response.status}: ${response.statusText}`,
      details: errorData,
    };
  }

  const data = await response.json();
  
  if (!data.success) {
    throw {
      code: data.error?.code || 'API_ERROR',
      message: data.error?.message || 'API request failed',
      details: data.error?.details,
    };
  }

  return data.data;
};

/**
 * Login user with credentials - redirects to central auth service
 */
export const login = async (_credentials: LoginRequest): Promise<AuthUser> => {
  // For centralized auth, redirect to the auth portal with return URL
  const returnUrl = encodeURIComponent(window.location.href);
  window.location.href = `http://127.0.0.1/auth/login?returnUrl=${returnUrl}`;
  
  // This will never execute due to redirect, but needed for TypeScript
  throw new Error('Redirecting to auth service...');
};

/**
 * Register new user
 */
export const register = async (userData: RegisterRequest): Promise<AuthUser> => {
  try {
    const data = await apiRequest<AuthUser>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(userData),
    });
    
    // Store token for future requests
    if (data.token) {
      localStorage.setItem('token', data.token);
    }
    
    return data;
  } catch (error) {
    return handleApiError(error);
  }
};

/**
 * Get current authenticated user info
 * First tries local token, then checks with central auth service
 */
export const getCurrentUser = async (): Promise<AuthUser | null> => {
  console.log('[AuthAPI] Checking current user...');
  
  try {
    const token = getStoredToken();
    console.log('[AuthAPI] Local token exists:', !!token);
    
    // First try with local token if available
    if (token) {
      try {
        console.log('[AuthAPI] Trying local token validation...');
        const data = await apiRequest<AuthUser>('/auth/user');
        console.log('[AuthAPI] Local token validated successfully:', data);
        return data;
      } catch (error) {
        console.log('[AuthAPI] Local token validation failed:', error);
        // If local token is invalid, clear it and continue to check central auth
        if (error && typeof error === 'object' && 'code' in error && error.code === 'UNAUTHORIZED') {
          localStorage.removeItem('token');
        }
      }
    }

    // Try to get user info from central auth service (cross-domain check)
    try {
      console.log('[AuthAPI] Trying central auth service check...');
      const centralHeaders: Record<string,string> = { 'Content-Type': 'application/json' };
      if (token) {
        centralHeaders['Authorization'] = `Bearer ${token}`;
      }
      const response = await fetch('http://127.0.0.1/auth/api/user', {
        method: 'GET',
        credentials: 'include', // Include cookies for cross-domain auth
        headers: centralHeaders,
      });

      console.log('[AuthAPI] Central auth response status:', response.status);
      
      if (response.ok) {
        const data = await response.json();
        console.log('[AuthAPI] Central auth response data:', data);
        
        if (data.success && data.data) {
          // Store the token locally for future requests
          if (data.data.token) {
            localStorage.setItem('token', data.data.token);
            console.log('[AuthAPI] Stored token from central auth');
          }
          return data.data;
        }
      }
    } catch (centralAuthError) {
      // Central auth service not available or user not logged in there
      console.log('[AuthAPI] Central auth check failed:', centralAuthError);
    }

    console.log('[AuthAPI] No authentication found');
    return null;
  } catch (error) {
    console.warn('[AuthAPI] Auth check failed:', error);
    return null;
  }
};

/**
 * Logout user (client-side only)
 */
export const logout = (): void => {
  localStorage.removeItem('token');
};

/**
 * Check if user is authenticated
 */
export const isAuthenticated = (): boolean => {
  return !!getStoredToken();
};

export default {
  login,
  register,
  getCurrentUser,
  logout,
  isAuthenticated,
};
