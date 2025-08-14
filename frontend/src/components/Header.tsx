import React from 'react';
import type { ProjectsData } from '../types/projects';
import { GitHubIcon } from './GitHubIcon';

interface HeaderProps {
  data: ProjectsData;
}

export const Header: React.FC<HeaderProps> = ({ data }) => {
  return (
    <header>
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
