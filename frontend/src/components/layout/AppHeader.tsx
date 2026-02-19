import React, { useEffect, useMemo, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { FeatureAuthStatus } from '../features/FeatureAuthStatus';
import { useAuth } from '../../stores/authStore';
import { XPWidget } from '../XPWidget';

type NavItem = {
  path: string;
  label: string;
};

export const AppHeader: React.FC = () => {
  const location = useLocation();
  const { isAuthenticated, user, logout } = useAuth();
  const isAdmin = user?.role === 'admin';
  const [mobileOpen, setMobileOpen] = useState(false);

  const navItems = useMemo<NavItem[]>(() => {
    const items: NavItem[] = [
      { path: '/', label: 'Home' },
      { path: '/tracker', label: 'Tracker' },
      { path: '/quests', label: 'Quests' },
      { path: '/leaderboard', label: 'Leaderboard' },
      { path: '/bosses', label: 'Bosses' },
      { path: '/features', label: 'Features' },
      { path: '/ideas', label: 'Ideas' },
      { path: '/about', label: 'About' },
    ];

    if (isAdmin) {
      items.push({ path: '/projects', label: 'Manage' });
    }

    return items;
  }, [isAdmin]);

  useEffect(() => {
    setMobileOpen(false);
  }, [location.pathname]);

  const isActive = (path: string) => location.pathname === path;

  const navClass = (path: string) => {
    const base =
      'inline-flex items-center rounded-lg border px-3 py-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/70';

    if (isActive(path)) {
      return `${base} border-cyan-500/40 bg-cyan-950/40 text-cyan-200`;
    }

    return `${base} border-slate-800 text-slate-300 hover:border-slate-700 hover:bg-slate-900 hover:text-white`;
  };

  return (
    <header className="sticky top-0 z-50 border-b border-slate-800 bg-slate-950/95 backdrop-blur-md">
      <div className="w-full px-4 sm:px-6 lg:px-8">
        <div className="flex h-16 items-center gap-3">
          <Link to="/" className="flex min-w-0 items-center gap-2.5">
            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-cyan-400 text-lg font-bold text-white shadow-lg shadow-cyan-500/20">
              W
            </span>
            <span className="truncate bg-gradient-to-r from-blue-300 to-cyan-200 bg-clip-text text-lg font-bold tracking-tight text-transparent sm:text-xl">
              WebHatchery
            </span>
          </Link>

          <nav className="hidden lg:flex items-center gap-2 overflow-x-auto whitespace-nowrap">
            {navItems.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={navClass(item.path)}
                aria-current={isActive(item.path) ? 'page' : undefined}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          <div className="ml-auto flex items-center gap-2 lg:gap-3">
            <div className="hidden xl:block">
              <XPWidget />
            </div>
            <div className="hidden lg:block">
              <FeatureAuthStatus />
            </div>
            <button
              type="button"
              onClick={() => setMobileOpen((prev) => !prev)}
              className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-700 bg-slate-900 text-slate-200 transition-colors hover:border-cyan-500/40 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/70 md:hidden"
              aria-label="Toggle menu"
              aria-expanded={mobileOpen}
            >
              <span className="text-xl leading-none">{mobileOpen ? 'X' : '='}</span>
            </button>
          </div>
        </div>

        {mobileOpen && (
          <div className="border-t border-slate-800 py-3 md:hidden">
            <nav className="grid grid-cols-2 gap-2">
              {navItems.map((item) => (
                <Link
                  key={`mobile-${item.path}`}
                  to={item.path}
                  className={navClass(item.path)}
                  aria-current={isActive(item.path) ? 'page' : undefined}
                >
                  {item.label}
                </Link>
              ))}
            </nav>

            <div className="mt-3 flex flex-wrap gap-2 border-t border-slate-800 pt-3">
              {isAuthenticated ? (
                <>
                  <Link to="/profile" className={navClass('/profile')}>
                    Profile
                  </Link>
                  <button
                    type="button"
                    onClick={logout}
                    className="inline-flex items-center rounded-lg border border-slate-800 px-3 py-2 text-sm font-medium text-slate-300 transition-colors hover:border-red-500/40 hover:bg-red-950/30 hover:text-red-200"
                  >
                    Logout
                  </button>
                </>
              ) : (
                <>
                  <Link to="/login" className={navClass('/login')}>
                    Login
                  </Link>
                  <Link
                    to="/register"
                    className="inline-flex items-center rounded-lg border border-cyan-500/40 bg-cyan-950/40 px-3 py-2 text-sm font-medium text-cyan-200 transition-colors hover:bg-cyan-900/40"
                  >
                    Sign Up
                  </Link>
                </>
              )}
            </div>
          </div>
        )}
      </div>
    </header>
  );
};
