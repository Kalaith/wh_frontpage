import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import { FeatureAuthStatus } from '../features/FeatureAuthStatus';
import { useFeatureRequestUser, useIsFeatureAuthenticated } from '../../stores/featureRequestStore';

export const AppHeader: React.FC = () => {
  const location = useLocation();
  const user = useFeatureRequestUser();
  const isAuthenticated = useIsFeatureAuthenticated();
  const isAdmin = isAuthenticated && user?.role === 'admin';

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
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                  isActive('/') 
                    ? 'bg-blue-100 text-blue-700' 
                    : 'text-gray-600 hover:text-blue-600 hover:bg-gray-50'
                }`}
              >
                Home
              </Link>
              <Link
                to="/tracker"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                  isActive('/tracker') 
                    ? 'bg-blue-100 text-blue-700' 
                    : 'text-gray-600 hover:text-blue-600 hover:bg-gray-50'
                }`}
              >
                Tracker
              </Link>
              <Link
                to="/features"
                className={`px-3 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-1 ${
                  isActive('/features') 
                    ? 'bg-green-100 text-green-700' 
                    : 'text-gray-600 hover:text-green-600 hover:bg-gray-50'
                }`}
              >
                <span className="text-base">🥚</span>
                Feature Requests
              </Link>
              {isAdmin && (
                <Link
                  to="/projects"
                  className={`px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                    isActive('/projects') 
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
              <option value="/features">🥚 Feature Requests</option>
              {isAdmin && <option value="/projects">Manage Projects</option>}
            </select>
          </div>

          {/* Authentication Status */}
          <div className="flex items-center">
            <FeatureAuthStatus />
          </div>
        </div>
      </div>
    </header>
  );
};