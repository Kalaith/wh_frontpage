import React, { useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';

export const AuthStatus: React.FC = () => {
  const { isLoading, isAuthenticated, user, checkAuth, logout } = useAuth();

  useEffect(() => {
    // AuthProvider performs the initial auth check; avoid duplicating it here.
  }, []);

  if (isLoading) {
    return (
      <div style={{ padding: '0.25rem 0.6rem', borderRadius: 6 }}>
        <span style={{ fontSize: '0.9rem' }}>Checking authentication…</span>
      </div>
    );
  }

  if (isAuthenticated && user) {
    return (
      <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
        <div style={{ padding: '0.25rem 0.6rem', borderRadius: 6 }}>
          Hi {user.firstName || user.displayName || user.email}
        </div>
        <button
          onClick={logout}
          className="btn btn-secondary"
          style={{ padding: '0.25rem 0.6rem', fontSize: '0.85rem' }}
        >
          Logout
        </button>
      </div>
    );
  }

  const AUTH_APP_URL =
    import.meta.env.VITE_AUTH_APP_URL || 'http://127.0.0.1/auth';

  return (
    <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
      <a
        href={`${AUTH_APP_URL}/login`}
        className="btn btn-primary"
        style={{ padding: '0.25rem 0.6rem', textDecoration: 'none' }}
      >
        Log in
      </a>
      <button
        onClick={() => checkAuth()}
        className="btn"
        style={{ padding: '0.25rem 0.6rem', fontSize: '0.85rem' }}
      >
        Retry
      </button>
    </div>
  );
};

export default AuthStatus;
