import React from 'react';
import type { ProjectsData } from '../types/projects';
import { getProjectUrl } from '../utils/projectUtils';

interface QuickLinksProps {
  data: ProjectsData;
}

export const QuickLinks: React.FC<QuickLinksProps> = ({ data }) => {
  return (
    <nav className="quick-links">
      <div className="links">
        {Object.entries(data.groups).map(([groupId, group]) => {
          if (group.hidden) return null;

          return (
            <div key={groupId} className="link-group">
              <h3>{group.name}</h3>
              <div className="link-items">
                {group.projects.map((project, index) => (
                  <a
                    key={`${groupId}-${index}`}
                    href={getProjectUrl(project)}
                    className="project-link"
                  >
                    {project.title}
                  </a>
                ))}
              </div>
            </div>
          );
        })}
      </div>
    </nav>
  );
};
