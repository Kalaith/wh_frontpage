import React from 'react';
import type { Project } from '../types/projects';
import { Badge } from './Badge';
import { GitHubIcon } from './GitHubIcon';
import {
  getProjectUrl,
  getDeploymentBadgeClass,
  getStageBadgeClass,
  getStatusBadgeClass,
  getStatusBadgeText,
} from '../utils/projectUtils';

interface ProjectCardProps {
  project: Project;
}

export const ProjectCard: React.FC<ProjectCardProps> = ({ project }) => {
  const projectUrl = getProjectUrl(project);

  return (
    <article className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden border-l-4 border-blue-500">
      <header className="p-6 pb-4 border-b border-gray-100">
        <h4 className="text-xl font-semibold mb-3 text-blue-600">
          <a
            href={projectUrl}
            className="hover:text-teal-500 transition-colors"
          >
            {project.title}
          </a>
        </h4>
        <div className="flex flex-wrap gap-2 items-center">
          {project.status && (
            <Badge variant="status" className={getStatusBadgeClass(project)}>
              {getStatusBadgeText(project)}
            </Badge>
          )}
          {project.stage && (
            <Badge variant="stage" className={getStageBadgeClass(project)}>
              {project.stage}
            </Badge>
          )}
          {project.deployment && (
            <Badge className={getDeploymentBadgeClass(project)}>
              {project.deployment.type}
            </Badge>
          )}
          {project.deployment?.requiresBuild && (
            <Badge className="bg-orange-50 text-orange-600">
              Build Required
            </Badge>
          )}
          {project.repository && (
            <a
              href={project.repository.url}
              className="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-50 text-gray-700 text-sm font-medium rounded border border-gray-200 hover:bg-gray-100 hover:text-blue-600 transition-colors"
              target="_blank"
              rel="noopener"
              title="View on GitHub"
            >
              <GitHubIcon />
              GitHub
            </a>
          )}
        </div>
      </header>
      <div className="p-6 pt-4">
        <p className="text-gray-600 leading-relaxed mb-4">
          {project.description}
        </p>

        {project.deployment && (
          <div className="flex flex-wrap gap-2">
            {project.deployment.packageManager && (
              <span className="inline-block bg-gray-100 text-gray-700 px-2 py-1 rounded text-sm border border-gray-200">
                {project.deployment.packageManager}
              </span>
            )}
            {project.deployment.backend && (
              <span className="inline-block bg-gray-100 text-gray-700 px-2 py-1 rounded text-sm border border-gray-200">
                {project.deployment.backend.type}
              </span>
            )}
          </div>
        )}
      </div>
      <footer className="px-6 py-4 bg-gray-50 border-t border-gray-100">
        <a
          href={projectUrl}
          className="inline-block text-blue-600 font-medium hover:text-teal-500 hover:underline transition-colors"
        >
          Explore Project →
        </a>
      </footer>
    </article>
  );
};
