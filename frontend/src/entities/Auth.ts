/**
 * Authentication entities and types
 */

export interface AuthUser {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  displayName?: string;
  membershipType: 'member' | 'admin' | 'premium';
  token: string;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
  confirmPassword?: string;
}

export interface ApiResponse<T = unknown> {
  success: boolean;
  data: T;
  error?: {
    code: string;
    message: string;
    details?: unknown;
  };
  message?: string;
}

export interface AuthError {
  code: string;
  message: string;
  details?: unknown;
}

export interface AuthState {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: AuthError | null;
}
