/**
 * Auth Store Tests
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useAuthStore } from './authStore';

// Mock the auth API
vi.mock('../api/authApi', () => ({
  login: vi.fn(),
  register: vi.fn(),
  logout: vi.fn(),
  getCurrentUser: vi.fn(),
}));

describe('AuthStore', () => {
  beforeEach(() => {
    // Reset the store state before each test
    useAuthStore.setState({
      user: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,
      sessionRestored: false,
    });
  });

  it('should initialize with default state', () => {
    const store = useAuthStore.getState();

    expect(store.user).toBeNull();
    expect(store.isAuthenticated).toBe(false);
    expect(store.isLoading).toBe(false);
    expect(store.error).toBeNull();
    expect(store.sessionRestored).toBe(false);
  });

  it('should set loading state', () => {
    const { setLoading } = useAuthStore.getState();

    setLoading(true);

    expect(useAuthStore.getState().isLoading).toBe(true);
  });

  it('should clear error', () => {
    const { setError, clearError } = useAuthStore.getState();

    setError({ code: 'TEST_ERROR', message: 'Test error' });
    expect(useAuthStore.getState().error).toEqual({
      code: 'TEST_ERROR',
      message: 'Test error',
    });

    clearError();
    expect(useAuthStore.getState().error).toBeNull();
  });

  it('should logout and clear user state', () => {
    const { logout } = useAuthStore.getState();

    // Set some user state first
    useAuthStore.setState({
      user: { id: 1, email: 'test@example.com' } as any,
      isAuthenticated: true,
    });

    logout();

    const state = useAuthStore.getState();
    expect(state.user).toBeNull();
    expect(state.isAuthenticated).toBe(false);
    expect(state.error).toBeNull();
  });
});
