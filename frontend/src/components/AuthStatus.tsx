import React from 'react';
import { useAuth } from '../stores/authStore';

export const AuthStatus: React.FC = () => {
  const { isLoading, isAuthenticated, user, logout } = useAuth();

  if (isLoading) {
    return (
      <div className="px-2 py-1 rounded">
        <span className="text-sm">Checking authentication…</span>
      </div>
    );
  }

  if (isAuthenticated && user) {
    return (
      <div className="flex items-center gap-2">
        <div className="px-2 py-1 rounded text-sm">
          Hi {user.firstName || user.displayName || user.email}
        </div>
        <button
          onClick={logout}
          className="px-2 py-1 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors"
        >
          Logout
        </button>
      </div>
    );
  }

  const AUTH_APP_URL =
    import.meta.env.VITE_AUTH_APP_URL || 'http://127.0.0.1/auth';

  return (
    <div className="flex items-center gap-2">
      <a
        href={`${AUTH_APP_URL}/login`}
        className="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded no-underline transition-colors"
      >
        Log in
      </a>
    </div>
  );
};

export default AuthStatus;
