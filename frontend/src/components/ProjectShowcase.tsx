import React from 'react';
import type { ProjectsData } from '../types/projects';
import { ProjectCard } from './ProjectCard';

interface ProjectShowcaseProps {
  data: ProjectsData;
}

export const ProjectShowcase: React.FC<ProjectShowcaseProps> = ({ data }) => {
  return (
    <main>
      <h2 className="text-2xl font-semibold text-center text-blue-600 mb-12">
        Project Portfolio
      </h2>
      {Object.entries(data.groups).map(([groupId, group]) => {
        if (group.hidden) return null;

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
