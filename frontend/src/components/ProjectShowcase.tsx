import React from 'react';
import type { ProjectsData } from '../types/projects';
import { ProjectCard } from './ProjectCard';

interface ProjectShowcaseProps {
  data?: ProjectsData;
  isLoading?: boolean;
}

export const ProjectShowcase: React.FC<ProjectShowcaseProps> = ({
  data,
  isLoading,
}) => {
  if (isLoading || !data) {
    return (
      <main className="animate-pulse">
        <div className="h-8 w-64 bg-gray-200 rounded mx-auto mb-12"></div>
        {[1, 2].map(i => (
          <section key={i} className="mb-16">
            <div className="h-6 w-48 bg-gray-200 rounded mx-auto mb-8 border-b-2 border-transparent"></div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
              {[1, 2, 3].map(j => (
                <div
                  key={j}
                  className="h-48 bg-gray-200 rounded-lg shadow-sm"
                ></div>
              ))}
            </div>
          </section>
        ))}
      </main>
    );
  }

  return (
    <main>
      <h2 className="text-2xl font-semibold text-center text-blue-600 mb-12">
        Project Portfolio
      </h2>
      {Object.entries(data.groups).map(([groupId, group]) => {
        return (
          <section key={groupId} className="mb-16" id={groupId}>
            <h3 className="text-xl font-semibold text-center text-blue-600 mb-8 pb-2 border-b-2 border-teal-500">
              {group.name}
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
              {group.projects.map((project, index) => (
                <ProjectCard key={`${groupId}-${index}`} project={project} />
              ))}
            </div>
          </section>
        );
      })}
    </main>
  );
};
