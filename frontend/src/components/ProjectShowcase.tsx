import React from 'react';
import type { ProjectsData } from '../types/projects';
import { GitHubIcon } from './GitHubIcon';
import {
  getProjectUrl,
  getStageBadgeClass,
  getStatusBadgeClass,
  getStatusBadgeText,
} from '../utils/projectUtils';

interface ProjectShowcaseProps {
  data?: ProjectsData;
  isLoading?: boolean;
}

export const ProjectShowcase: React.FC<ProjectShowcaseProps> = ({
  data,
  isLoading,
}) => {
  const getRepoUrl = (project: {
    repository?: { url?: string };
    repository_url?: string;
    repositoryUrl?: string;
  }): string | undefined => {
    return (
      project.repository?.url ??
      project.repository_url ??
      project.repositoryUrl ??
      undefined
    );
  };

  if (isLoading || !data) {
    return (
      <main className="animate-pulse">
        <div className="h-8 w-64 bg-gray-200 rounded mx-auto mb-10"></div>
        {[1, 2].map(i => (
          <section key={i} className="mb-12">
            <div className="h-6 w-48 bg-gray-200 rounded mx-auto mb-8 border-b-2 border-transparent"></div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
              {[1, 2, 3, 4, 5, 6].map(j => (
                <div
                  key={j}
                  className="h-28 bg-gray-200 rounded-lg shadow-sm"
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
      {Object.entries(data.groups).map(([groupId, group]) => {
        return (
          <section key={groupId} className="mb-12" id={groupId}>
            <h3 className="text-xl font-semibold text-center text-blue-600 mb-8 pb-2 border-b-2 border-teal-500">
              {group.name}
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 items-start gap-4 mb-8">
              {group.projects.map((project, index) => {
                const repoUrl = getRepoUrl(project);
                const projectUrl = getProjectUrl(project);
                return (
                <article
                  key={`${groupId}-${index}`}
                  className="self-start bg-white rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow p-4 cursor-pointer"
                  role="link"
                  tabIndex={0}
                  onClick={() => {
                    window.location.href = projectUrl;
                  }}
                  onKeyDown={e => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      e.preventDefault();
                      window.location.href = projectUrl;
                    }
                  }}
                >
                  <div className="flex items-start justify-between gap-3">
                    <h4
                      className="text-base font-semibold text-blue-700 truncate"
                      title={project.title}
                    >
                      {project.title}
                    </h4>
                    <div className="flex items-center justify-end gap-2 text-xs shrink-0">
                      {project.status && (
                        <span
                          className={`px-2 py-1 rounded font-medium ${getStatusBadgeClass(project)}`}
                        >
                          {getStatusBadgeText(project)}
                        </span>
                      )}
                      {project.stage && (
                        <span
                          className={`px-2 py-1 rounded font-medium ${getStageBadgeClass(project)}`}
                        >
                          {project.stage}
                        </span>
                      )}
                      {repoUrl && (
                        <a
                          href={repoUrl}
                          className="inline-flex items-center justify-center p-1.5 rounded bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors"
                          target="_blank"
                          rel="noopener noreferrer"
                          title={repoUrl}
                          aria-label="Open GitHub repository"
                          onClick={e => e.stopPropagation()}
                          onKeyDown={e => e.stopPropagation()}
                        >
                          <GitHubIcon width={14} height={14} className="text-current" />
                        </a>
                      )}
                    </div>
                  </div>
                  <p
                    className="mt-1 text-sm text-slate-600 leading-5 overflow-hidden break-words"
                    style={{
                      marginBottom: 0,
                      display: '-webkit-box',
                      WebkitLineClamp: 2,
                      WebkitBoxOrient: 'vertical',
                    }}
                    title={project.description}
                  >
                    {project.description}
                  </p>
                </article>
                );
              })}
            </div>
          </section>
        );
      })}
    </main>
  );
};
