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

export interface ApiResponse<T = any> {
  success: boolean;
  data: T;
  error?: {
    code: string;
    message: string;
    details?: any;
  };
  message?: string;
}

export interface AuthError {
  code: string;
  message: string;
  details?: any;
}

export interface AuthState {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: AuthError | null;
}
