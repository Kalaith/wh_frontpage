import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { FeatureAuthStatus } from '../features/FeatureAuthStatus';
import { useAuth } from '../../stores/authStore';
import { XPWidget } from '../XPWidget';

export const AppHeader: React.FC = () => {
  const location = useLocation();
  const { isAuthenticated, user } = useAuth();
  const isAdmin = user?.role === 'admin';

  const isActive = (path: string) => location.pathname === path;

  // Common styles for nav links
  const getNavLinkClass = (path: string) => {
    const baseClass = "px-3 py-2 rounded-md text-sm font-medium transition-all duration-200 flex items-center gap-1.5 border";
    if (isActive(path)) {
      return `${baseClass} bg-cyan-950/40 text-cyan-300 border-cyan-500/30 shadow-[0_0_10px_rgba(34,211,238,0.1)]`;
    }
    return `${baseClass} text-slate-400 border-transparent hover:text-cyan-200 hover:bg-slate-800/50 hover:border-slate-700`;
  };

  return (
    <header className="bg-slate-950/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo and Main Navigation */}
          <div className="flex items-center space-x-8">
            <Link
              to="/"
              className="flex items-center space-x-2 group"
            >
              <div className="w-8 h-8 bg-gradient-to-br from-blue-600 to-cyan-400 rounded-lg flex items-center justify-center shadow-lg group-hover:shadow-cyan-500/20 transition-all">
                <span className="text-white font-bold text-lg">W</span>
              </div>
              <span className="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-cyan-300">
                WebHatchery
              </span>
            </Link>

            <nav className="hidden md:flex space-x-1">
              <Link to="/" className={getNavLinkClass('/')}>
                Home
              </Link>
              <Link to="/tracker" className={getNavLinkClass('/tracker')}>
                Tracker
              </Link>
              <Link to="/quests" className={getNavLinkClass('/quests')}>
                <span className="text-base">🔮</span> Quests
              </Link>
              <Link to="/leaderboard" className={getNavLinkClass('/leaderboard')}>
                <span className="text-base">🏆</span> Leaderboard
              </Link>
              <Link to="/bosses" className={getNavLinkClass('/bosses')}>
                <span className="text-base">⚔️</span> Bosses
              </Link>
              <Link to="/features" className={getNavLinkClass('/features')}>
                <span className="text-base">🥚</span> Features
              </Link>
              <Link to="/ideas" className={getNavLinkClass('/ideas')}>
                <span className="text-base">💡</span> Ideas
              </Link>
              <Link to="/about" className={getNavLinkClass('/about')}>
                About
              </Link>
              {isAdmin && (
                <Link to="/projects" className={getNavLinkClass('/projects')}>
                  Manage
                </Link>
              )}
            </nav>
          </div>

          {/* Mobile Navigation Menu */}
          <div className="md:hidden">
            <select
              value={location.pathname}
              onChange={(e) => window.location.pathname = e.target.value}
              className="block w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-md text-slate-200 focus:outline-none focus:ring-2 focus:ring-cyan-500"
            >
              <option value="/">Home</option>
              <option value="/tracker">Tracker</option>
              <option value="/quests">🔮 Quests</option>
              <option value="/leaderboard">🏆 Leaderboard</option>
              <option value="/bosses">⚔️ Boss Battle</option>
              <option value="/features">🥚 Feature Requests</option>
              <option value="/ideas">💡 Ideas</option>
              <option value="/about">About</option>
              {isAuthenticated && <option value="/profile">Profile</option>}
              {isAdmin && <option value="/projects">Manage Projects</option>}
            </select>
          </div>

          {/* Authentication Status */}
          <div className="flex items-center gap-4">
            <div className="hidden sm:block">
              <XPWidget />
            </div>
            <FeatureAuthStatus />
          </div>
        </div>
      </div>
    </header>
  );
};
