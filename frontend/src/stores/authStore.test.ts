/**
 * Auth Store Tests
 */
import { describe, it, expect, beforeEach } from 'vitest';
import { useAuthStore } from './authStore';

describe('AuthStore', () => {
  beforeEach(() => {
    // Reset the store state before each test.
    useAuthStore.setState({
      user: null,
      token: null,
      isAuthenticated: false,
    });
  });

  it('should initialize with default state', () => {
    const store = useAuthStore.getState();

    expect(store.user).toBeNull();
    expect(store.token).toBeNull();
    expect(store.isAuthenticated).toBe(false);
  });

  it('should set auth state when setAuth is called', () => {
    const { setAuth } = useAuthStore.getState();
    const testUser = {
      id: 1,
      email: 'test@example.com',
      username: 'tester',
      firstName: 'Test',
      lastName: 'User',
      role: 'user' as const,
      egg_balance: 0,
      can_claim_daily: false,
      member_since: '2026-01-01',
      is_verified: false,
      display_name: 'Test User',
    };

    setAuth(testUser, 'test-token');

    const state = useAuthStore.getState();
    expect(state.user).toEqual(testUser);
    expect(state.token).toBe('test-token');
    expect(state.isAuthenticated).toBe(true);
  });
});
