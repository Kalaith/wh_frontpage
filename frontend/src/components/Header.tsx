import React from 'react';
import { Link } from 'react-router-dom';
import type { ProjectsData } from '../types/projects';
import { GitHubIcon } from './GitHubIcon';
import { useAuth } from '../contexts/AuthContext';

interface HeaderProps {
  data: ProjectsData;
}

export const Header: React.FC<HeaderProps> = ({ data }) => {
  const { user, isAuthenticated } = useAuth();
  // Some auth responses include a 'membershipType' field, others return a 'roles' array.
  const hasAdminRole = isAuthenticated && (
    user?.membershipType === 'admin' ||
    (Array.isArray((user as any)?.roles) && (user as any).roles.includes('admin'))
  );
  const isAdmin = !!hasAdminRole;

  return (
    <header>
      {/* Top login bar: admin-only controls live here */}
      <div className="login-bar" style={{ display: 'flex', justifyContent: 'flex-end', gap: '0.5rem', marginBottom: '0.5rem' }}>
        {isAdmin && (
          <Link to="/projects">Manage Projects</Link>
        )}
      </div>
      <h1>Welcome to WebHatchery.au</h1>
      <p className="tagline">Where ideas hatch into websites.</p>
      <p className="description">
        {data.description || 'This is a development landing page for web experiments, game previews, and digital prototypes.'}
      </p>
      {data.version && (
        <p className="version">Platform Version: {data.version}</p>
      )}
      
      {data.global?.repository && (
        <div className="main-repository">
          <a 
            href={data.global.repository.url} 
            className="main-github-link" 
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
