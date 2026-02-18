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
  role: 'user' | 'member' | 'admin' | 'premium' | 'guild_master';
  membershipType?: 'user' | 'member' | 'admin' | 'premium' | 'guild_master'; // Compatibility
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
  username: string;
  confirmPassword?: string;
}

export interface AuthState {
  user: AuthUser | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: AuthError | null;
}
