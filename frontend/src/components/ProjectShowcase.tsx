import React, { useState } from 'react';
import type { ProjectsData } from '../types/projects';
import { getProjectCatalog, projectTypes } from '../utils/projectCatalog';
import { ProjectCard } from './ProjectCard';

interface ProjectShowcaseProps {
  data?: ProjectsData;
  isLoading?: boolean;
  recentUpdates?: React.ReactNode;
}

export const ProjectShowcase: React.FC<ProjectShowcaseProps> = ({
  data,
  isLoading,
  recentUpdates,
}) => {
  const [filter, setFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [technology, setTechnology] = useState('all');
  const [showFilters, setShowFilters] = useState(false);
  const [density, setDensity] = useState<'cards' | 'compact'>(() => {
    try {
      return localStorage.getItem('hatchery-density') === 'compact'
        ? 'compact'
        : 'cards';
    } catch {
      return 'cards';
    }
  });
  const catalog = getProjectCatalog(data);
  const types = projectTypes.filter(type =>
    catalog.some(entry => entry.type === type)
  );
  const technologies = ['Rust', 'React', 'Static'].filter(tech =>
    catalog.some(entry => entry.technologies.includes(tech))
  );
  const query = search.trim().toLowerCase();
  const matching = catalog.filter(
    ({ project, technologies: tech }) =>
      (technology === 'all' || tech.includes(technology)) &&
      `${project.display_name ?? ''} ${project.title} ${project.description}`
        .toLowerCase()
        .includes(query)
  );
  const visibleGroups = types
    .map(type => ({
      type,
      entries: matching.filter(
        entry => entry.type === type && (filter === 'all' || filter === type)
      ),
    }))
    .filter(group => group.entries.length > 0);
  const visibleCount = visibleGroups.reduce(
    (total, group) => total + group.entries.length,
    0
  );
  const hasFilters = !!query || filter !== 'all' || technology !== 'all';

  const clearFilters = () => {
    setSearch('');
    setFilter('all');
    setTechnology('all');
  };

  return (
    <section
      className="hatchery-explore"
      id="explore"
      aria-labelledby="explore-title"
    >
      <div className="hatchery-index-heading">
        <h1 id="explore-title">
          {isLoading ? 'Loading projects…' : `${catalog.length} projects`}
        </h1>
        <p>Experiments, games and unfinished ideas.</p>
      </div>
      <div className="hatchery-toolbar">
        <label className="hatchery-search">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            aria-hidden="true"
          >
            <circle cx="10.5" cy="10.5" r="6.5" />
            <path d="m16 16 5 5" />
          </svg>
          <span className="sr-only">Search projects</span>
          <input
            type="search"
            value={search}
            onChange={event => setSearch(event.target.value)}
            placeholder="Search all projects…"
          />
        </label>
        <div
          className="hatchery-view-controls"
          role="group"
          aria-label="Project display"
        >
          {(['cards', 'compact'] as const).map(view => (
            <button
              key={view}
              type="button"
              aria-pressed={density === view}
              onClick={() => {
                setDensity(view);
                try {
                  localStorage.setItem('hatchery-density', view);
                } catch {
                  /* Browsing still works when storage is unavailable. */
                }
              }}
            >
              <span aria-hidden="true">{view === 'cards' ? '▦' : '☷'}</span>{' '}
              {view === 'cards' ? 'Cards' : 'Compact'}
            </button>
          ))}
        </div>
      </div>
      <div className="hatchery-filter-bar">
        <div
          className="hatchery-filters"
          role="group"
          aria-label="Filter by project type"
        >
          <button
            type="button"
            aria-pressed={filter === 'all'}
            onClick={() => setFilter('all')}
          >
            All <span>{catalog.length}</span>
          </button>
          {types.map(type => (
            <button
              key={type}
              type="button"
              aria-pressed={filter === type}
              onClick={() => setFilter(type)}
            >
              {type}{' '}
              <span>{catalog.filter(entry => entry.type === type).length}</span>
            </button>
          ))}
        </div>
        {technologies.length > 0 && (
          <button
            type="button"
            className="hatchery-filter-toggle"
            aria-expanded={showFilters}
            aria-controls="technology-filters"
            onClick={() => setShowFilters(!showFilters)}
          >
            Filters{technology !== 'all' ? ` · ${technology}` : ''}{' '}
            <span aria-hidden="true">{showFilters ? '−' : '+'}</span>
          </button>
        )}
      </div>
      {showFilters && (
        <div
          id="technology-filters"
          className="hatchery-technology-filters hatchery-filters"
          role="group"
          aria-label="Filter by technology"
        >
          <span>Technology</span>
          {['all', ...technologies].map(tech => (
            <button
              key={tech}
              type="button"
              aria-pressed={technology === tech}
              onClick={() => setTechnology(tech)}
            >
              {tech === 'all' ? 'Any' : tech}
            </button>
          ))}
        </div>
      )}
      {recentUpdates}
      {!isLoading && hasFilters && (
        <div className="hatchery-results-summary">
          <span role="status">
            {visibleCount} of {catalog.length} projects
          </span>
          <button
            type="button"
            className="hatchery-text-link"
            onClick={clearFilters}
          >
            Clear filters
          </button>
        </div>
      )}
      {isLoading ? (
        <div
          className="hatchery-project-grid"
          role="status"
          aria-label="Loading projects"
        >
          {[...Array<number>(12).keys()].map(i => (
            <div className="hatchery-skeleton" key={i} />
          ))}
        </div>
      ) : visibleGroups.length ? (
        visibleGroups.map(group => (
          <section
            key={group.type}
            id={group.type.toLowerCase()}
            className="hatchery-project-section"
            aria-labelledby={`heading-${group.type}`}
          >
            <div className="hatchery-group-heading">
              <h2 id={`heading-${group.type}`}>
                {group.type === 'Apps' ? 'Apps & Experiments' : group.type}
              </h2>
              <span>{group.entries.length}</span>
            </div>
            <div
              className={
                density === 'compact'
                  ? 'hatchery-project-list'
                  : 'hatchery-project-grid'
              }
            >
              {group.entries.map(({ project }, index) => (
                <ProjectCard
                  key={project.id ?? `${group.type}-${index}`}
                  project={project}
                  groupName={group.type}
                />
              ))}
            </div>
          </section>
        ))
      ) : (
        <div className="hatchery-empty">
          <h2>
            {hasFilters
              ? 'No projects match these filters.'
              : 'New ideas are on their way.'}
          </h2>
          <p>
            {hasFilters
              ? 'Try another search or clear the filters to see every project.'
              : 'Check back soon for new projects.'}
          </p>
        </div>
      )}
    </section>
  );
};
