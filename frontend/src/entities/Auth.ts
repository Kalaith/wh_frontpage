/**
 * Authentication entities and types
 */

import type { AuthError } from '../types/common';

export interface AuthUser {
  id: number;
  email: string;
  username: string;
  firstName: string;
  lastName: string;
  display_name?: string;
  displayName?: string; // Compatibility
  role: 'member' | 'admin' | 'premium';
  membershipType?: 'member' | 'admin' | 'premium'; // Compatibility
  egg_balance: number;
  can_claim_daily: boolean;
  member_since: string;
  is_verified: boolean;
  token?: string;
  isActive?: boolean;
  createdAt?: string;
  updatedAt?: string;
  stats?: {
    features_created: number;
    votes_cast: number;
  };
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
