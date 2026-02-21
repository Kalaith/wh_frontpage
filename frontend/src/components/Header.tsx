import React from 'react';
import { Link } from 'react-router-dom';
import type { ProjectsData } from '../types/projects';
import { GitHubIcon } from './GitHubIcon';
import {
  useFeatureRequestUser,
  useIsFeatureAuthenticated,
} from '../stores/featureRequestStore';

interface HeaderProps {
  data: ProjectsData;
}

export const Header: React.FC<HeaderProps> = ({ data }) => {
  const user = useFeatureRequestUser();
  const isAuthenticated = useIsFeatureAuthenticated();
  // Check if user is admin using the feature request user model
  const isAdmin = isAuthenticated && user?.role === 'admin';

  return (
    <header className="text-center mb-12">
      {/* Top navigation bar */}
      <div className="flex justify-between items-center mb-2">
        <nav className="flex gap-2">
          {/* Tracker link removed to focus on Quest Board */}
        </nav>
        <div className="flex gap-2">
          {isAdmin && (
            <Link
              to="/projects"
              className="px-3 py-1.5 rounded-lg text-blue-600 hover:bg-blue-50 font-medium transition-colors"
            >
              Manage Projects
            </Link>
          )}
        </div>
      </div>
      <h1 className="text-4xl font-bold mb-4 text-blue-600">
        Welcome to WebHatchery.au
      </h1>
      <p className="text-lg italic text-teal-500 mb-4">
        Where ideas hatch into websites.
      </p>
      <p className="text-lg leading-relaxed max-w-2xl mx-auto mb-4 text-gray-700">
        {data.description ||
          'Your gateway to experimental web apps, game previews, and digital prototypes. Claim quests, build features, and earn rewards.'}
      </p>
      {data.version && (
        <p className="text-sm text-teal-500 italic mb-0">
          Platform Version: {data.version}
        </p>
      )}

      {data.global?.repository && (
        <div className="mt-6">
          <a
            href={data.global.repository.url}
            className="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-800 to-gray-900 text-white rounded-lg hover:from-gray-900 hover:to-gray-800 font-medium transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5"
            target="_blank"
            rel="noopener"
            title={`View ${data.global.repository.name} on GitHub`}
          >
            <GitHubIcon width={20} height={20} />
            <span>View on GitHub</span>
          </a>
        </div>
      )}
    </header>
  );
};
