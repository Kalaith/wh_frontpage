import React from 'react';
import type { Project } from '../types/projects';
import { GitHubIcon } from './GitHubIcon';
import {
  getProjectDisplayName,
  getProjectUrl,
  getStatusBadgeClass,
  getStatusBadgeText,
} from '../utils/projectUtils';

interface ArchiveSectionProps {
  projects?: Project[];
  isLoading?: boolean;
}

const getRepoUrl = (project: Project): string | undefined =>
  project.repository?.url ?? project.repository_url ?? undefined;

/**
 * Retired / archived projects, surfaced in a muted, collapsible section at the
 * bottom of the homepage. Sourced from project_roost (archived = true).
 */
export const ArchiveSection: React.FC<ArchiveSectionProps> = ({
  projects,
  isLoading,
}) => {
  if (isLoading || !projects || projects.length === 0) {
    return null;
  }

  return (
    <section className="mt-16 border-t border-slate-200 pt-8" id="archive">
      <details className="group">
        <summary className="cursor-pointer list-none flex items-center justify-center gap-2 text-slate-500 hover:text-slate-700 transition-colors select-none">
          <span className="text-lg font-semibold">Archive</span>
          <span className="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
            {projects.length}
          </span>
          <span className="text-xs text-slate-400 group-open:hidden">
            (show)
          </span>
          <span className="text-xs text-slate-400 hidden group-open:inline">
            (hide)
          </span>
        </summary>

        <p className="text-center text-sm text-slate-400 mt-2 mb-6">
          Older projects that are no longer actively featured.
        </p>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 items-start gap-4">
          {projects.map((project, index) => {
            const repoUrl = getRepoUrl(project);
            const projectUrl = getProjectUrl(project);
            const displayName = getProjectDisplayName(project);
            return (
              <article
                key={`archive-${index}`}
                className="self-start bg-slate-50 rounded-lg border border-slate-200 opacity-80 hover:opacity-100 hover:shadow-sm transition p-4 cursor-pointer"
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
                    className="text-base font-semibold text-slate-600 truncate"
                    title={displayName}
                  >
                    {displayName}
                  </h4>
                  <div className="flex items-center justify-end gap-2 text-xs shrink-0">
                    <span className="px-2 py-1 rounded font-medium bg-slate-200 text-slate-500">
                      Archived
                    </span>
                    {project.status && (
                      <span
                        className={`px-2 py-1 rounded font-medium ${getStatusBadgeClass(project)}`}
                      >
                        {getStatusBadgeText(project)}
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
                  className="mt-1 text-sm text-slate-500 leading-5 overflow-hidden break-words"
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
      </details>
    </section>
  );
};
