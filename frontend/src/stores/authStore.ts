/**
 * Authentication Store using Zustand
 * Manages authentication state throughout the application
 */
import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import type { AuthUser } from '../entities/Auth';
import api from '../api/api';

interface AuthState {
  user: AuthUser | null;
  token: string | null;
  isAuthenticated: boolean;

  // Actions
  setAuth: (user: AuthUser, token: string) => void;
  login: (email: string, password: string) => Promise<AuthUser>;
  register: (userData: any) => Promise<AuthUser>;
  logout: () => void;
  refreshUserInfo: () => Promise<void>;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,

      setAuth: (user, token) => {
        set({ user, token, isAuthenticated: true });
      },

      refreshUserInfo: async () => {
        try {
          const response = await api.getCurrentUser();
          if (response.success && response.data) {
            set({ user: response.data, isAuthenticated: true });
          }
        } catch (error) {
          console.error('Failed to refresh user info:', error);
        }
      },

      login: async (email, password) => {
        const response = await api.login(email, password);
        if (response.success && response.data) {
          const { user, token } = response.data;
          set({ user, token, isAuthenticated: true });
          return user;
        }
        const message = typeof response.error === 'string'
          ? response.error
          : response.error?.message || 'Login failed';
        throw new Error(message);
      },

      register: async (userData) => {
        const response = await api.register(userData);
        if (response.success && response.data) {
          const { user, token } = response.data;
          set({ user, token, isAuthenticated: true });
          return user;
        }
        const message = typeof response.error === 'string'
          ? response.error
          : response.error?.message || 'Registration failed';
        throw new Error(message);
      },

      logout: () => {
        // Clear all project-related storage
        Object.keys(localStorage).forEach(key => {
          if (key.includes('frontpage') || key.includes('auth-storage')) {
            localStorage.removeItem(key);
          }
        });

        set({ user: null, token: null, isAuthenticated: false });

        // Force reload to clear memory
        window.location.href = '/';
      },
    }),
    {
      name: 'auth-storage',
    }
  )
);

// Compatibility hook for existing components
export const useAuth = () => {
  const store = useAuthStore();

  return {
    ...store,
    isAdmin: store.user?.role === 'admin',
    loginWithRedirect: () => { window.location.href = '/login'; },
    isLoading: false, // Legacy field
    error: null,      // Legacy field
  };
};
