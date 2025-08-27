/**
 * Authentication Store using Zustand
 * Manages authentication state throughout the application
 */
import React from 'react';
import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { useCurrentUser, useLogin, useRegister, useLogout } from '../hooks/useAuthQuery';
import type { AuthUser } from '../entities/Auth';

// Simple auth store for UI state only
// React Query handles server state
interface AuthStore {
  // Local UI state
  isAuthenticated: boolean;
  user: AuthUser | null;
  
  // Actions
  setAuth: (user: AuthUser | null) => void;
  clearAuth: () => void;
}

export const useAuthStore = create<AuthStore>()(
  persist(
    (set) => ({
      // Initial state
      user: null,
      isAuthenticated: false,

      // Actions
      setAuth: (user: AuthUser | null) => {
        set({ user, isAuthenticated: !!user });
      },
      
      clearAuth: () => {
        set({ user: null, isAuthenticated: false });
      },
    }),
    {
      name: 'auth-storage',
      partialize: state => ({
        user: state.user,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);

// Combined hook that uses both React Query for server state and Zustand for UI state
export const useAuth = () => {
  const { user, isAuthenticated, setAuth, clearAuth } = useAuthStore();
  const { data: queryUser, isLoading, error } = useCurrentUser();
  const loginMutation = useLogin();
  const registerMutation = useRegister();
  const logoutMutation = useLogout();

  // Sync React Query data with Zustand store
  React.useEffect(() => {
    if (queryUser && (!user || user.id !== queryUser.id)) {
      setAuth(queryUser);
    } else if (!queryUser && user) {
      clearAuth();
    }
  }, [queryUser, user, setAuth, clearAuth]);

  return {
    user: user || queryUser,
    isAuthenticated: isAuthenticated && !!queryUser,
    isLoading,
    error,
    login: async (email: string, password: string) => {
      try {
        const result = await loginMutation.mutateAsync({ email, password });
        setAuth(result);
        return result;
      } catch (error) {
        clearAuth();
        throw error;
      }
    },
    register: async (userData: {
      email: string;
      password: string;
      firstName: string;
      lastName: string;
    }) => {
      try {
        const result = await registerMutation.mutateAsync(userData);
        setAuth(result);
        return result;
      } catch (error) {
        clearAuth();
        throw error;
      }
    },
    logout: () => {
      logoutMutation.mutate();
      clearAuth();
    },
  };
};
