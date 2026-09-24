import React from 'react';
import type { Project } from '../types/projects';
import { GitHubIcon } from './GitHubIcon';
import { getProjectDisplayName, getProjectUrl } from '../utils/projectUtils';
import {
  getProjectCategory,
  getVisitorStatus,
} from '../utils/projectPresentation';

interface ProjectCardProps {
  project: Project;
  groupName?: string;
}

export const ProjectCard: React.FC<ProjectCardProps> = ({
  project,
  groupName,
}) => {
  const name = getProjectDisplayName(project);
  const category = getProjectCategory(project, groupName);
  const status = getVisitorStatus(project.status);
  const repoUrl =
    project.repository?.url ?? project.repository_url ?? project.repositoryUrl;

  return (
    <article className="hatchery-project-card">
      <h3>
        <a href={getProjectUrl(project)} className="hatchery-project-link">
          {name}
        </a>
      </h3>
      <p title={project.description}>{project.description}</p>
      <div className="hatchery-card-top">
        <span className="hatchery-category">{category}</span>
        <div className="hatchery-card-labels">
          {repoUrl && (
            <a
              href={repoUrl}
              className="hatchery-repo"
              target="_blank"
              rel="noopener noreferrer"
              aria-label={`View ${name} source on GitHub`}
            >
              <GitHubIcon width={17} height={17} />
            </a>
          )}
          <span className={`hatchery-status is-${status.tone}`}>
            <span aria-hidden="true" />
            {status.label}
          </span>
        </div>
      </div>
    </article>
  );
};
