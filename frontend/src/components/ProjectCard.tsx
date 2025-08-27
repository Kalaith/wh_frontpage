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
    <article className="project-card">
      <header className="project-header">
        <h4>
          <a href={projectUrl}>{project.title}</a>
        </h4>
        <div className="project-meta">
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
          {project.version && (
            <Badge variant="version">v{project.version}</Badge>
          )}
          {project.deployment?.requiresBuild && (
            <Badge className="badge-build">Build Required</Badge>
          )}
          {project.repository && (
            <a
              href={project.repository.url}
              className="github-link"
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
      <div className="project-content">
        <p className="project-description">{project.description}</p>

        {project.deployment && (
          <div className="deployment-info">
            {project.deployment.packageManager && (
              <span className="tech-tag">
                {project.deployment.packageManager}
              </span>
            )}
            {project.deployment.backend && (
              <span className="tech-tag">
                {project.deployment.backend.type}
              </span>
            )}
          </div>
        )}
      </div>
      <footer className="project-footer">
        <a href={projectUrl} className="project-link-btn">
          Explore Project →
        </a>
      </footer>
    </article>
  );
};
