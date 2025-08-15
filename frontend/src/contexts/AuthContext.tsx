/**
 * Authentication Context
 * Provides authentication state and methods throughout the app
 */
import React, { createContext, useContext, useEffect, useState } from 'react';
import type { ReactNode } from 'react';
import type { AuthState } from '../entities/Auth';
import * as authApi from '../api/authApi';

interface AuthContextType extends AuthState {
  login: (email: string, password: string) => Promise<void>;
  register: (userData: { email: string; password: string; firstName: string; lastName: string }) => Promise<void>;
  logout: () => void;
  checkAuth: () => Promise<void>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [state, setState] = useState<AuthState>({
    user: null,
    isAuthenticated: false,
    isLoading: true,
    error: null,
  });

  // Initialize auth state on mount
  useEffect(() => {
    const initAuth = async () => {
      try {
        if (authApi.isAuthenticated()) {
          const user = await authApi.getCurrentUser();
          if (user) {
            setState({
              user,
              isAuthenticated: true,
              isLoading: false,
              error: null,
            });
          } else {
            // Token exists but user fetch failed, clear auth
            authApi.logout();
            setState({
              user: null,
              isAuthenticated: false,
              isLoading: false,
              error: null,
            });
          }
        } else {
          setState({
            user: null,
            isAuthenticated: false,
            isLoading: false,
            error: null,
          });
        }
      } catch (error) {
        console.error('Auth initialization failed:', error);
        setState({
          user: null,
          isAuthenticated: false,
          isLoading: false,
          error: error as any,
        });
      }
    };

    initAuth();
  }, []);

  const login = async (email: string, password: string): Promise<void> => {
    setState(prev => ({ ...prev, isLoading: true, error: null }));
    
    try {
      const user = await authApi.login({ email, password });
      setState({
        user,
        isAuthenticated: true,
        isLoading: false,
        error: null,
      });
    } catch (error) {
      setState(prev => ({
        ...prev,
        isLoading: false,
        error: error as any,
      }));
      throw error;
    }
  };

  const register = async (userData: { email: string; password: string; firstName: string; lastName: string }): Promise<void> => {
    setState(prev => ({ ...prev, isLoading: true, error: null }));
    
    try {
      const user = await authApi.register(userData);
      setState({
        user,
        isAuthenticated: true,
        isLoading: false,
        error: null,
      });
    } catch (error) {
      setState(prev => ({
        ...prev,
        isLoading: false,
        error: error as any,
      }));
      throw error;
    }
  };

  const logout = (): void => {
    authApi.logout();
    setState({
      user: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,
    });
  };

  const checkAuth = async (): Promise<void> => {
    try {
      setState(prev => ({ ...prev, isLoading: true }));
      const user = await authApi.getCurrentUser();
      if (user) {
        setState(prev => ({ ...prev, user, isAuthenticated: true, isLoading: false }));
      } else {
        logout();
      }
    } catch (error) {
      console.error('Failed to check auth:', error);
      logout();
    }
  };

  // alias for older name
  const refreshUser = checkAuth;

  const value: AuthContextType = {
    ...state,
    login,
    register,
    logout,
    checkAuth,
    refreshUser,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
