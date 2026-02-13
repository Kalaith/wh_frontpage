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

  return (
    <header className="bg-white shadow-sm border-b border-gray-200">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo and Main Navigation */}
          <div className="flex items-center space-x-8">
            <Link
              to="/"
              className="flex items-center space-x-2"
            >
              <span className="text-xl font-bold text-blue-600">WebHatchery.au</span>
            </Link>

            <nav className="hidden md:flex space-x-6">
              <Link
                to="/"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${isActive('/')
                  ? 'bg-blue-100 text-blue-700'
                  : 'text-gray-600 hover:text-blue-600 hover:bg-gray-50'
                  }`}
              >
                Home
              </Link>
              <Link
                to="/tracker"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${isActive('/tracker')
                  ? 'bg-blue-100 text-blue-700'
                  : 'text-gray-600 hover:text-blue-600 hover:bg-gray-50'
                  }`}
              >
                Tracker
              </Link>
              <Link
                to="/quests"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${isActive('/quests')
                  ? 'bg-purple-100 text-purple-700'
                  : 'text-gray-600 hover:text-purple-600 hover:bg-gray-50'
                  }`}
              >
                🔮 Quests
              </Link>
              <Link
                to="/leaderboard"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${isActive('/leaderboard')
                  ? 'bg-yellow-100 text-yellow-700'
                  : 'text-gray-600 hover:text-yellow-600 hover:bg-gray-50'
                  }`}
              >
                🏆 Leaderboard
              </Link>
              <Link
                to="/bosses"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${isActive('/bosses')
                  ? 'bg-red-100 text-red-700'
                  : 'text-gray-600 hover:text-red-600 hover:bg-gray-50'
                  }`}
              >
                ⚔️ Boss Battle
              </Link>
              <Link
                to="/features"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-1 ${isActive('/features')
                  ? 'bg-green-100 text-green-700'
                  : 'text-gray-600 hover:text-green-600 hover:bg-gray-50'
                  }`}
              >
                <span className="text-base">🥚</span>
                Feature Requests
              </Link>
              <Link
                to="/ideas"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-1 ${isActive('/ideas')
                  ? 'bg-yellow-100 text-yellow-700'
                  : 'text-gray-600 hover:text-yellow-600 hover:bg-gray-50'
                  }`}
              >
                <span className="text-base">💡</span>
                Ideas
              </Link>
              <Link
                to="/about"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${isActive('/about')
                  ? 'bg-gray-100 text-gray-800'
                  : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
                  }`}
              >
                About
              </Link>
              {isAdmin && (
                <Link
                  to="/projects"
                  className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${isActive('/projects')
                    ? 'bg-purple-100 text-purple-700'
                    : 'text-gray-600 hover:text-purple-600 hover:bg-gray-50'
                    }`}
                >
                  Manage Projects
                </Link>
              )}
            </nav>
          </div>

          {/* Mobile Navigation Menu */}
          <div className="md:hidden">
            <select
              value={location.pathname}
              onChange={(e) => window.location.pathname = e.target.value}
              className="block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
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
            <XPWidget />
            <FeatureAuthStatus />
          </div>
        </div>
      </div>
    </header>
  );
};