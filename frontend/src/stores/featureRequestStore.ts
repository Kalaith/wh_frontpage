import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { User } from '../types/featureRequest';
import { featureRequestApi } from '../api/featureRequestApi';

interface FeatureRequestState {
  user: User | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
}

interface FeatureRequestActions {
  login: (email: string, password: string) => Promise<void>;
  register: (data: {
    username: string;
    email: string;
    password: string;
    display_name?: string;
  }) => Promise<void>;
  logout: () => void;
  clearError: () => void;
  updateProfile: (data: { display_name?: string; username?: string }) => Promise<void>;
  claimDailyEggs: () => Promise<{ success: boolean; message: string; eggsEarned?: number }>;
  refreshProfile: () => Promise<void>;
  setUser: (user: User) => void;
}

export const useFeatureRequestStore = create<FeatureRequestState & FeatureRequestActions>()(
  persist(
    (set, get) => ({
      // State
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,

      // Actions
      login: async (email: string, password: string) => {
        set({ isLoading: true, error: null });
        
        try {
          const response = await featureRequestApi.login({ email, password });
          const { user, token } = response;
          
          // Store token in localStorage for API requests
          localStorage.setItem('token', token);
          
          set({
            user,
            token,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });
        } catch (error: any) {
          set({
            user: null,
            token: null,
            isAuthenticated: false,
            isLoading: false,
            error: error.message || 'Login failed',
          });
          throw error;
        }
      },

      register: async (data) => {
        set({ isLoading: true, error: null });
        
        try {
          await featureRequestApi.register(data);
          
          set({
            user: null, // User needs to login after registration
            token: null,
            isAuthenticated: false,
            isLoading: false,
            error: null,
          });
        } catch (error: any) {
          set({
            isLoading: false,
            error: error.message || 'Registration failed',
          });
          throw error;
        }
      },

      logout: () => {
        localStorage.removeItem('token');
        set({
          user: null,
          token: null,
          isAuthenticated: false,
          error: null,
        });
      },

      clearError: () => {
        set({ error: null });
      },

      updateProfile: async (data) => {
        set({ isLoading: true, error: null });
        
        try {
          const updatedUser = await featureRequestApi.updateProfile(data);
          set({
            user: updatedUser,
            isLoading: false,
            error: null,
          });
        } catch (error: any) {
          set({
            isLoading: false,
            error: error.message || 'Profile update failed',
          });
          throw error;
        }
      },

      claimDailyEggs: async () => {
        const { user } = get();
        if (!user) {
          throw new Error('User not authenticated');
        }

        try {
          const result = await featureRequestApi.claimDailyEggs();
          
          // Update user's egg balance
          set({
            user: {
              ...user,
              egg_balance: result.new_balance,
              can_claim_daily: false,
            },
          });
          
          return {
            success: true,
            message: `Claimed ${result.eggs_earned} eggs!`,
            eggsEarned: result.eggs_earned,
          };
        } catch (error: any) {
          return {
            success: false,
            message: error.message || 'Failed to claim daily eggs',
          };
        }
      },

      refreshProfile: async () => {
        const { isAuthenticated } = get();
        if (!isAuthenticated) return;

        try {
          const updatedUser = await featureRequestApi.getProfile();
          set({ user: updatedUser });
        } catch (error: any) {
          // If token is invalid, logout
          if (error.status === 401) {
            get().logout();
          }
        }
      },

      setUser: (user: User) => {
        set({ user });
      },
    }),
    {
      name: 'feature-request-auth-storage',
      partialize: (state) => ({
        user: state.user,
        token: state.token,
        isAuthenticated: state.isAuthenticated,
      }),
      onRehydrateStorage: () => (state) => {
        // Restore token to localStorage on app load
        if (state?.token) {
          localStorage.setItem('token', state.token);
        }
      },
    }
  )
);

// Helper hooks with proper memoization
export const useFeatureRequestUser = () => useFeatureRequestStore((state) => state.user);
export const useIsFeatureAuthenticated = () => useFeatureRequestStore((state) => state.isAuthenticated);
export const useIsFeatureAdmin = () => useFeatureRequestStore((state) => state.user?.role === 'admin');

// Individual action hooks to prevent re-render issues
export const useFeatureLogin = () => useFeatureRequestStore((state) => state.login);
export const useFeatureRegister = () => useFeatureRequestStore((state) => state.register);
export const useFeatureLogout = () => useFeatureRequestStore((state) => state.logout);
export const useFeatureClaimDailyEggs = () => useFeatureRequestStore((state) => state.claimDailyEggs);
export const useFeatureClearError = () => useFeatureRequestStore((state) => state.clearError);
export const useFeatureUpdateProfile = () => useFeatureRequestStore((state) => state.updateProfile);
export const useFeatureRefreshProfile = () => useFeatureRequestStore((state) => state.refreshProfile);

// For backward compatibility, but using a stable selector
const actionsSelector = (state: FeatureRequestState & FeatureRequestActions) => ({
  login: state.login,
  register: state.register,
  logout: state.logout,
  clearError: state.clearError,
  updateProfile: state.updateProfile,
  claimDailyEggs: state.claimDailyEggs,
  refreshProfile: state.refreshProfile,
});

export const useFeatureRequestActions = () => useFeatureRequestStore(actionsSelector);