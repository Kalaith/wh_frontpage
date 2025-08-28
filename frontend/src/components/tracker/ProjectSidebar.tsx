import React from 'react';
import type { ProjectsData } from '../../types/projects';

interface ProjectSidebarProps {
  projectsData?: ProjectsData;
  selectedProjectId?: number | null;
  onProjectSelect: (projectId: number | null) => void;
  isLoading?: boolean;
}

const ProjectSidebar: React.FC<ProjectSidebarProps> = ({
  projectsData,
  selectedProjectId,
  onProjectSelect,
  isLoading = false
}) => {
  if (isLoading) {
    return (
      <div className="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4">
        <div className="text-center py-4">
          <p className="text-gray-500 dark:text-gray-400">Loading projects...</p>
        </div>
      </div>
    );
  }

  if (!projectsData) {
    return (
      <div className="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4">
        <div className="text-center py-4">
          <p className="text-gray-500">No projects found</p>
        </div>
      </div>
    );
  }

  return (
    <div className="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
      <div className="p-4 border-b border-gray-200 dark:border-gray-700">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Projects</h2>
      </div>
      
      <div className="p-4">
        {/* All Projects Option */}
        <button
          onClick={() => onProjectSelect(null)}
          className={`w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors ${
            selectedProjectId === null
              ? 'bg-blue-100 text-blue-700'
              : 'text-gray-700 hover:bg-gray-100'
          }`}
        >
          All Projects
        </button>

        {/* Grouped Projects */}
        <div className="mt-4 space-y-4">
          {Object.entries(projectsData.groups || {}).map(([groupKey, group]) => (
            <div key={groupKey}>
              <h3 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                {group.name}
              </h3>
              <div className="space-y-1">
                {group.projects.map((project) => (
                  <button
                    key={project.id}
                    onClick={() => onProjectSelect(project.id || null)}
                    className={`w-full text-left px-3 py-2 rounded-md text-sm transition-colors ${
                      selectedProjectId === project.id
                        ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    }`}
                    title={project.description}
                  >
                    <div className="truncate">{project.title}</div>
                    <div className="text-xs text-gray-500 dark:text-gray-400">
                      {project.stage} • {project.status}
                    </div>
                  </button>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default ProjectSidebar;