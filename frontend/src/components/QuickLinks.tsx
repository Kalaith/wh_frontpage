import React from 'react';
import type { ProjectsData } from '../types/projects';
import { getProjectUrl } from '../utils/projectUtils';

interface QuickLinksProps {
  data?: ProjectsData;
  isLoading?: boolean;
}

export const QuickLinks: React.FC<QuickLinksProps> = ({ data, isLoading }) => {
  if (isLoading || !data) {
    return (
      <nav className="mb-12 animate-pulse">
        <div className="flex flex-wrap justify-center gap-8 mb-2">
          {[1, 2, 3].map(i => (
            <div key={i} className="text-center mb-8">
              <div className="h-6 w-24 bg-gray-200 rounded-full mx-auto mb-4"></div>
              <div className="flex flex-wrap justify-center gap-4">
                {[1, 2].map(j => (
                  <div
                    key={j}
                    className="h-12 w-40 bg-gray-200 rounded-lg"
                  ></div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </nav>
    );
  }

  return (
    <nav className="mb-12">
      <div className="flex flex-wrap justify-center gap-8 mb-2">
        {Object.entries(data.groups).map(([groupId, group]) => {
          return (
            <div key={groupId} className="text-center mb-8">
              <h3 className="inline-block px-3 py-1 mb-4 text-sm font-bold text-blue-600 uppercase tracking-wide bg-gradient-to-r from-blue-50 to-teal-25 rounded-full shadow-sm">
                {group.name}
              </h3>
              <div className="flex flex-wrap justify-center gap-4">
                {group.projects.map((project, index) => (
                  <a
                    key={`${groupId}-${index}`}
                    href={getProjectUrl(project)}
                    className="inline-block px-6 py-3 text-white font-medium bg-blue-600 hover:bg-teal-500 rounded-lg shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 min-w-40 text-center"
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
