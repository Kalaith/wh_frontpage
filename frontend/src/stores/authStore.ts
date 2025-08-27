/**
 * Authentication Store using Zustand
 * Manages authentication state throughout the application
 */
import React from 'react';
import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import * as authApi from '../api/authApi';
import type { AuthState, AuthError } from '../entities/Auth';

// Authentication state interface
interface AuthStoreState extends AuthState {
  sessionRestored: boolean;
}

// Authentication actions interface
interface AuthActions {
  // Authentication methods
  login: (email: string, password: string) => Promise<void>;
  register: (userData: {
    email: string;
    password: string;
    firstName: string;
    lastName: string;
  }) => Promise<void>;
  logout: () => void;

  // Session management
  restoreSession: () => Promise<void>;
  clearSession: () => void;
  checkAuth: () => Promise<void>;
  refreshUser: () => Promise<void>;

  // Error handling
  clearError: () => void;
  setError: (error: AuthError | null) => void;

  // Loading state
  setLoading: (loading: boolean) => void;
}

type AuthStore = AuthStoreState & AuthActions;

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      // Initial state
      user: null,
      isAuthenticated: false,
      isLoading: true,
      error: null,
      sessionRestored: false,

      // Authentication methods
      login: async (email: string, password: string) => {
        set({ isLoading: true, error: null });

        try {
          const user = await authApi.login({ email, password });
          set({
            user,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });

          // Dispatch a custom event for successful login
          const loginEvent = new CustomEvent('auth:login-success');
          window.dispatchEvent(loginEvent);
        } catch (error) {
          const authError: AuthError = {
            code: 'LOGIN_FAILED',
            message: error instanceof Error ? error.message : 'Login failed',
          };
          set({
            error: authError,
            isLoading: false,
            isAuthenticated: false,
            user: null,
          });
          throw error;
        }
      },

      register: async (userData: {
        email: string;
        password: string;
        firstName: string;
        lastName: string;
      }) => {
        set({ isLoading: true, error: null });

        try {
          const user = await authApi.register(userData);
          set({
            user,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });
        } catch (error) {
          const authError: AuthError = {
            code: 'REGISTRATION_FAILED',
            message:
              error instanceof Error ? error.message : 'Registration failed',
          };
          set({
            error: authError,
            isLoading: false,
            isAuthenticated: false,
            user: null,
          });
          throw error;
        }
      },

      logout: () => {
        authApi.logout();
        set({
          user: null,
          isAuthenticated: false,
          isLoading: false,
          error: null,
        });
      },

      // Session management
      restoreSession: async () => {
        if (get().sessionRestored) return;

        set({ isLoading: true });

        try {
          const user = await authApi.getCurrentUser();
          if (user) {
            set({
              user,
              isAuthenticated: true,
              isLoading: false,
              error: null,
              sessionRestored: true,
            });
          } else {
            set({
              user: null,
              isAuthenticated: false,
              isLoading: false,
              error: null,
              sessionRestored: true,
            });
          }
        } catch (error) {
          console.error('Session restoration error:', error);
          set({
            user: null,
            isAuthenticated: false,
            isLoading: false,
            error: null,
            sessionRestored: true,
          });
        }
      },

      clearSession: () => {
        set({
          user: null,
          isAuthenticated: false,
          error: null,
          sessionRestored: false,
        });
      },

      checkAuth: async () => {
        try {
          set({ isLoading: true });
          const user = await authApi.getCurrentUser();
          if (user) {
            set({
              user,
              isAuthenticated: true,
              isLoading: false,
              error: null,
            });
          } else {
            get().logout();
          }
        } catch (error) {
          console.error('Failed to check auth:', error);
          get().logout();
        }
      },

      // Alias for checkAuth to maintain compatibility
      refreshUser: async () => {
        await get().checkAuth();
      },

      // Error handling
      clearError: () => set({ error: null }),
      setError: (error: AuthError | null) => set({ error }),

      // Loading state
      setLoading: (loading: boolean) => set({ isLoading: loading }),
    }),
    {
      name: 'auth-storage',
      partialize: state => ({
        // Only persist essential data, not loading states
        sessionRestored: state.sessionRestored,
      }),
    }
  )
);

// Computed selectors for common authentication checks
export const useAuth = () => {
  const store = useAuthStore();

  return {
    ...store,
  };
};

// Hook to automatically restore session on app initialization
export const useAuthInitialization = () => {
  const restoreSession = useAuthStore(state => state.restoreSession);
  const sessionRestored = useAuthStore(state => state.sessionRestored);

  React.useEffect(() => {
    if (!sessionRestored) {
      restoreSession();
    }
  }, [restoreSession, sessionRestored]);
};
