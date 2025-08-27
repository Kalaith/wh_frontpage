import React from 'react';
import type { ProjectsData } from '../types/projects';

interface FooterProps {
  data: ProjectsData;
}

export const Footer: React.FC<FooterProps> = ({ data }) => {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="site-footer">
      <div className="footer-content">
        <p>&copy; {currentYear} WebHatchery.au · All rights reserved</p>
        {data.global?.buildTools && (
          <div className="tech-requirements">
            <small>
              Platform Requirements:{' '}
              {Object.entries(data.global.buildTools).map(
                ([tool, version], index) => (
                  <span key={tool}>
                    {index > 0 && ' '}
                    {tool} {version}
                  </span>
                )
              )}
            </small>
          </div>
        )}
      </div>
    </footer>
  );
};
