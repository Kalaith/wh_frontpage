/**
 * Authentication entities and types
 */

import type { AuthError } from '../types/common';

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

export interface AuthState {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: AuthError | null;
}
