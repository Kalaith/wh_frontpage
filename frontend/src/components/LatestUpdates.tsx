import React from 'react';
import { Link } from 'react-router-dom';
import { useProjectUpdates } from '../hooks/useProjectUpdates';
import type { ProjectUpdate } from '../types/projectUpdates';

interface LatestUpdatesProps {
  showAll?: boolean;
}

function getUpdateUrl(project: ProjectUpdate): string | undefined {
  // Never use the update feed's path: it can be a local filesystem path.
  return (
    [project.productionUrl, project.previewUrl, project.publicUrl].find(
      url => !!url && /^(https?:\/\/|\/(?!\/))/i.test(url)
    ) ?? undefined
  );
}

export const LatestUpdates: React.FC<LatestUpdatesProps> = ({
  showAll = false,
}) => {
  const {
    data: updates = [],
    isLoading,
    error,
    refetch,
    isFetching,
  } = useProjectUpdates();
  const visibleUpdates = showAll ? updates : updates.slice(0, 5);

  return (
    <section
      aria-label={showAll ? 'All project updates' : 'Recently updated'}
      className={showAll ? 'hatchery-updates' : 'hatchery-recent-strip'}
    >
      {!showAll && <h2>Recently updated</h2>}
      {isLoading ? (
        <p role="status" className="hatchery-feed-message">
          Loading the latest hatches…
        </p>
      ) : error ? (
        <div role="status" className="hatchery-feed-message">
          <p>Project updates couldn’t be loaded.</p>
          <button
            type="button"
            onClick={() => void refetch()}
            disabled={isFetching}
            className="hatchery-text-link"
          >
            {isFetching ? 'Trying again…' : 'Try again →'}
          </button>
        </div>
      ) : visibleUpdates.length === 0 ? (
        <p className="hatchery-feed-message">
          No project updates yet. Check back soon.
        </p>
      ) : (
        <ol
          className={showAll ? 'hatchery-update-list' : 'hatchery-recent-links'}
        >
          {visibleUpdates.map(project => {
            const url = getUpdateUrl(project);
            const name = project.displayName ?? project.name;
            const Card = url ? 'a' : 'div';
            return (
              <li key={`${project.type}-${project.path}-${project.name}`}>
                <Card
                  href={url}
                  className={
                    showAll ? 'hatchery-update-card' : 'hatchery-recent-link'
                  }
                >
                  {showAll ? (
                    <>
                      <div className="hatchery-update-meta">
                        <time dateTime={project.lastUpdated}>
                          {new Date(
                            project.lastUpdated ?? ''
                          ).toLocaleDateString(undefined, {
                            day: 'numeric',
                            month: 'short',
                            ...(showAll ? { year: 'numeric' as const } : {}),
                          })}
                        </time>
                        <span>
                          {['game_apps', 'games', 'rust_games'].includes(
                            project.type
                          )
                            ? 'Game'
                            : project.type === 'frontpage'
                              ? 'WebHatchery'
                              : 'App & experiment'}
                        </span>
                      </div>
                      <h3>{name}</h3>
                    </>
                  ) : (
                    name
                  )}
                </Card>
              </li>
            );
          })}
        </ol>
      )}
      {!showAll && (
        <Link to="/updates" className="hatchery-text-link">
          All updates <span aria-hidden="true">→</span>
        </Link>
      )}
    </section>
  );
};
