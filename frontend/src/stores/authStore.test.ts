/**
 * Auth Store Tests
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAuthStore } from './authStore';

// Mock the hooks
vi.mock('../hooks/useAuthQuery', () => ({
  useCurrentUser: vi.fn(() => ({ data: null, isLoading: false, error: null })),
  useLogin: vi.fn(() => ({ mutateAsync: vi.fn() })),
  useRegister: vi.fn(() => ({ mutateAsync: vi.fn() })),
  useLogout: vi.fn(() => ({ mutate: vi.fn() })),
}));

describe('AuthStore', () => {
  beforeEach(() => {
    // Reset the store state before each test
    useAuthStore.setState({
      user: null,
      isAuthenticated: false,
    });
  });

  it('should initialize with default state', () => {
    const store = useAuthStore.getState();

    expect(store.user).toBeNull();
    expect(store.isAuthenticated).toBe(false);
  });

  it('should set auth state when user is provided', () => {
    const { setAuth } = useAuthStore.getState();
    const testUser = { 
      id: 1, 
      email: 'test@example.com', 
      role: 'user', 
      display_name: 'Test User' 
    };

    setAuth(testUser);

    const state = useAuthStore.getState();
    expect(state.user).toEqual(testUser);
    expect(state.isAuthenticated).toBe(true);
  });

  it('should clear auth state when null user is provided', () => {
    const { setAuth } = useAuthStore.getState();
    
    // First set a user
    const testUser = { 
      id: 1, 
      email: 'test@example.com', 
      role: 'user', 
      display_name: 'Test User' 
    };
    setAuth(testUser);
    expect(useAuthStore.getState().isAuthenticated).toBe(true);

    // Then clear it
    setAuth(null);
    
    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.isAuthenticated).toBe(false);
  });

  it('should clear auth state with clearAuth', () => {
    const { setAuth, clearAuth } = useAuthStore.getState();

    // Set some user state first
    const testUser = { 
      id: 1, 
      email: 'test@example.com', 
      role: 'user', 
      display_name: 'Test User' 
    };
    setAuth(testUser);
    expect(useAuthStore.getState().isAuthenticated).toBe(true);

    clearAuth();

    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.isAuthenticated).toBe(false);
  });
});
