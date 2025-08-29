/**
 * Authentication API Client
 * Handles all authentication-related API requests
 */
import type { AuthUser, LoginRequest, RegisterRequest } from '../entities/Auth';

// Base URL for the local auth service (feature request system)
// Use the local backend API instead of the centralized auth service
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

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
      message:
        'Unable to connect to the server. Please check your connection or try again later.',
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


/**
 * Login user with credentials - uses local auth system
 */
export const login = async (credentials: LoginRequest): Promise<AuthUser> => {
  try {
    const response = await fetch(`${API_BASE_URL}/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(credentials),
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw {
        code: response.status === 401 ? 'UNAUTHORIZED' : 'API_ERROR',
        message: errorData.message || `HTTP ${response.status}: ${response.statusText}`,
        details: errorData,
      };
    }

    const data = await response.json();

    if (!data.success) {
      throw {
        code: 'API_ERROR',
        message: data.message || 'Login failed',
        details: data.error,
      };
    }

    // Store token for future requests
    if (data.data.token) {
      localStorage.setItem('token', data.data.token);
      // Also store it for the feature request API
      localStorage.setItem('auth_token', data.data.token);
    }

    // Convert our User format to AuthUser format for login response
    const authUser: AuthUser = {
      id: data.data.user.id,
      email: data.data.user.email,
      firstName: data.data.user.display_name?.split(' ')[0] || data.data.user.username,
      lastName: data.data.user.display_name?.split(' ').slice(1).join(' ') || '',
      membershipType: data.data.user.role === 'admin' ? 'admin' : 'member',
      token: data.data.token,
      isActive: data.data.user.is_verified,
      createdAt: data.data.user.member_since || '',
      updatedAt: '',
    };
    return authUser;
  } catch (error) {
    return handleApiError(error);
  }
};

/**
 * Register new user
 */
export const register = async (
  userData: RegisterRequest
): Promise<AuthUser> => {
  try {
    // Convert from old format to new format
    const registerData = {
      username: userData.email.split('@')[0], // Use email prefix as username
      email: userData.email,
      password: userData.password,
      display_name: `${userData.firstName} ${userData.lastName}`.trim(),
    };

    const response = await fetch(`${API_BASE_URL}/auth/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(registerData),
    });

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw {
        code: response.status === 400 ? 'VALIDATION_ERROR' : 'API_ERROR',
        message: errorData.message || `HTTP ${response.status}: ${response.statusText}`,
        details: errorData,
      };
    }

    const data = await response.json();

    if (!data.success) {
      throw {
        code: 'API_ERROR',
        message: data.message || 'Registration failed',
        details: data.error,
      };
    }

    // Convert our User format to AuthUser format for registration response
    const authUser: AuthUser = {
      id: data.data.id,
      email: data.data.email,
      firstName: data.data.display_name?.split(' ')[0] || data.data.username,
      lastName: data.data.display_name?.split(' ').slice(1).join(' ') || '',
      membershipType: data.data.role === 'admin' ? 'admin' : 'member',
      token: '', // No token provided during registration
      isActive: data.data.is_verified,
      createdAt: data.data.member_since || '',
      updatedAt: '',
    };
    return authUser;
  } catch (error) {
    return handleApiError(error);
  }
};

/**
 * Get current authenticated user info
 * Uses local token to validate with our feature request API
 */
export const getCurrentUser = async (): Promise<AuthUser | null> => {
  const AUTH_DEBUG =
    import.meta.env.DEV || import.meta.env.VITE_DEBUG_AUTH === 'true';
  const authDebug = (...args: any[]) => {
    if (AUTH_DEBUG) console.log(...args);
  };
  const authWarn = (...args: any[]) => {
    if (AUTH_DEBUG) console.warn(...args);
  };

  authDebug('[AuthAPI] Checking current user...');

  try {
    const token = getStoredToken();
    authDebug('[AuthAPI] Local token exists:', !!token);

    if (!token) {
      authDebug('[AuthAPI] No token found');
      return null;
    }

    try {
      authDebug('[AuthAPI] Validating token with local API...');
      const response = await fetch(`${API_BASE_URL}/user/profile`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      authDebug('[AuthAPI] Profile response status:', response.status);

      if (!response.ok) {
        if (response.status === 401) {
          // Token is invalid, clear it
          localStorage.removeItem('token');
          localStorage.removeItem('auth_token');
          authDebug('[AuthAPI] Token invalid, cleared storage');
          return null;
        }
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();
      authDebug('[AuthAPI] Profile response data:', data);

      if (data.success && data.data) {
        // Convert our User format to AuthUser format
        const authUser: AuthUser = {
          id: data.data.id,
          email: data.data.email,
          firstName: data.data.display_name?.split(' ')[0] || data.data.username,
          lastName: data.data.display_name?.split(' ').slice(1).join(' ') || '',
          membershipType: data.data.role === 'admin' ? 'admin' : 'member',
          token: token,
          isActive: data.data.is_verified,
          createdAt: data.data.member_since || '',
          updatedAt: '',
        };
        authDebug('[AuthAPI] User validated successfully:', authUser);
        return authUser;
      }
    } catch (error) {
      authDebug('[AuthAPI] Token validation failed:', error);
      localStorage.removeItem('token');
      localStorage.removeItem('auth_token');
      return null;
    }

    authDebug('[AuthAPI] No authentication found');
    return null;
  } catch (error) {
    authWarn('[AuthAPI] Auth check failed:', error);
    return null;
  }
};

/**
 * Logout user (client-side only)
 */
export const logout = (): void => {
  localStorage.removeItem('token');
  localStorage.removeItem('auth_token');
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
