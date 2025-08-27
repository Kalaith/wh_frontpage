import React from 'react';
import type { ProjectsData } from '../types/projects';
import { ProjectCard } from './ProjectCard';

interface ProjectShowcaseProps {
  data: ProjectsData;
}

export const ProjectShowcase: React.FC<ProjectShowcaseProps> = ({ data }) => {
  return (
    <main className="project-showcase">
      <h2>Project Portfolio</h2>
      {Object.entries(data.groups).map(([groupId, group]) => {
        if (group.hidden) return null;

        return (
          <section key={groupId} className="group" id={groupId}>
            <h3>{group.name}</h3>
            <div className="project-grid">
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
