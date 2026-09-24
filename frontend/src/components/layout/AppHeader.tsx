import React, { useEffect, useMemo, useState } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { FeatureAuthStatus } from '../features/FeatureAuthStatus';
import { useAuth } from '../../stores/authStore';
import { XPWidget } from '../XPWidget';
import { HatchlingMark } from '../HatchlingMark';

type NavItem = {
  path: string;
  label: string;
  external?: boolean;
};

export const AppHeader: React.FC = () => {
  const location = useLocation();
  const { isAuthenticated, logout } = useAuth();
  const [mobileOpen, setMobileOpen] = useState(false);

  const navItems = useMemo<NavItem[]>(() => {
    const items: NavItem[] = [
      { path: '/', label: 'Explore' },
      { path: '/about', label: 'About' },
      { path: '/project_roost/', label: 'Project Roost', external: true },
    ];

    return items;
  }, []);

  useEffect(() => {
    setMobileOpen(false);
  }, [location.pathname]);

  const isActive = (path: string) => location.pathname === path;

  const navClass = (path: string) => {
    return `hatchery-nav-link ${isActive(path) ? 'is-active' : ''}`;
  };

  return (
    <header
      className="hatchery-header sticky top-0 z-50"
      onKeyDown={event => {
        if (event.key === 'Escape') setMobileOpen(false);
      }}
    >
      <div className="hatchery-container">
        <div className="flex h-16 items-center gap-3">
          <Link to="/" className="hatchery-brand">
            <HatchlingMark />
            <span>WebHatchery</span>
          </Link>

          <nav className="hidden lg:flex items-center gap-2 overflow-x-auto whitespace-nowrap">
            {navItems.map(item =>
              item.external ? (
                <a
                  key={item.path}
                  href={item.path}
                  className={navClass(item.path)}
                >
                  {item.label}
                </a>
              ) : (
                <Link
                  key={item.path}
                  to={item.path}
                  className={navClass(item.path)}
                  aria-current={isActive(item.path) ? 'page' : undefined}
                >
                  {item.label}
                </Link>
              )
            )}
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
              onClick={() => setMobileOpen(prev => !prev)}
              className="hatchery-menu-button lg:hidden"
              aria-label="Toggle menu"
              aria-expanded={mobileOpen}
              aria-controls="mobile-navigation"
            >
              <span className="text-xl leading-none">
                {mobileOpen ? '×' : '☰'}
              </span>
            </button>
          </div>
        </div>

        {mobileOpen && (
          <div
            id="mobile-navigation"
            className="hatchery-mobile-nav py-3 lg:hidden"
          >
            <nav className="grid grid-cols-2 gap-2">
              {navItems.map(item =>
                item.external ? (
                  <a
                    key={`mobile-${item.path}`}
                    href={item.path}
                    onClick={() => setMobileOpen(false)}
                    className={navClass(item.path)}
                  >
                    {item.label}
                  </a>
                ) : (
                  <Link
                    key={`mobile-${item.path}`}
                    to={item.path}
                    onClick={() => setMobileOpen(false)}
                    className={navClass(item.path)}
                    aria-current={isActive(item.path) ? 'page' : undefined}
                  >
                    {item.label}
                  </Link>
                )
              )}
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
                    className="hatchery-nav-link"
                  >
                    Logout
                  </button>
                </>
              ) : (
                <>
                  <Link to="/login" className={navClass('/login')}>
                    Sign in
                  </Link>
                  <Link to="/register" className="hatchery-nav-link is-active">
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
