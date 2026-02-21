import React from 'react';
import { motion } from 'framer-motion';
import {
  useRecentProjectUpdates,
  useProjectsNeedingAttention,
} from '../hooks/useProjectUpdates';
import type { ProjectUpdate } from '../types/projectUpdates';

const ProjectUpdates: React.FC = () => {
  const {
    data: recentUpdates,
    isLoading: recentLoading,
    error: recentError,
  } = useRecentProjectUpdates();
  const { data: attentionProjects } = useProjectsNeedingAttention();

  if (recentLoading) {
    return (
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-semibold mb-4">Project Updates</h3>
        <div className="animate-pulse">
          <div className="h-4 bg-gray-200 rounded mb-2"></div>
          <div className="h-4 bg-gray-200 rounded mb-2"></div>
          <div className="h-4 bg-gray-200 rounded"></div>
        </div>
      </div>
    );
  }

  if (recentError) {
    return (
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-semibold mb-4">Project Updates</h3>
        <div className="text-red-600 text-sm">
          Unable to load project updates
        </div>
      </div>
    );
  }

  const recentProjects = (recentUpdates as ProjectUpdate[] | null) ?? [];
  const needsAttention = (attentionProjects as ProjectUpdate[] | null) ?? [];

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="bg-white rounded-lg shadow-sm border p-6"
    >
      <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
        <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
        Project Updates
      </h3>

      {/* Projects Needing Attention */}
      {needsAttention.length > 0 && (
        <div className="mb-6">
          <h4 className="text-sm font-medium text-orange-600 mb-2 flex items-center gap-1">
            <span className="w-1.5 h-1.5 bg-orange-500 rounded-full"></span>
            Needs Attention ({needsAttention.length})
          </h4>
          <div className="space-y-2">
            {needsAttention.slice(0, 3).map(project => (
              <ProjectUpdateCard
                key={project.name}
                project={project}
                variant="attention"
              />
            ))}
          </div>
        </div>
      )}

      {/* Recent Updates */}
      {recentProjects.length > 0 ? (
        <div>
          <h4 className="text-sm font-medium text-gray-600 mb-3">
            Recent Updates ({recentProjects.length})
          </h4>
          <div className="space-y-2">
            {recentProjects.slice(0, 5).map(project => (
              <ProjectUpdateCard
                key={project.name}
                project={project}
                variant="recent"
              />
            ))}
          </div>
        </div>
      ) : (
        <div className="text-gray-500 text-sm">No recent project updates</div>
      )}
    </motion.div>
  );
};

interface ProjectUpdateCardProps {
  project: ProjectUpdate;
  variant: 'recent' | 'attention';
}

const ProjectUpdateCard: React.FC<ProjectUpdateCardProps> = ({
  project,
  variant,
}) => {
  const getTypeColor = (type: string) => {
    switch (type) {
      case 'frontpage':
        return 'bg-blue-100 text-blue-800';
      case 'apps':
        return 'bg-green-100 text-green-800';
      case 'game_apps':
        return 'bg-purple-100 text-purple-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getUrgencyColor = (urgency: string) => {
    switch (urgency) {
      case 'today':
        return 'bg-green-100 text-green-800';
      case 'recent':
        return 'bg-blue-100 text-blue-800';
      case 'moderate':
        return 'bg-yellow-100 text-yellow-800';
      case 'stale':
        return 'bg-red-100 text-red-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getDeploymentIcon = (status: string) => {
    switch (status) {
      case 'production':
        return '🚀';
      case 'development_only':
        return '🔨';
      case 'not_deployed':
        return '⚠️';
      default:
        return '❓';
    }
  };

  const formatTimeAgo = (dateString?: string) => {
    if (!dateString) return 'Unknown';

    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
    const diffDays = Math.floor(diffHours / 24);

    if (diffHours < 1) return 'Just now';
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
  };

  const borderColor =
    variant === 'attention' ? 'border-orange-200' : 'border-gray-200';

  return (
    <motion.div
      whileHover={{ scale: 1.01 }}
      className={`p-3 rounded-lg border ${borderColor} bg-gray-50 hover:bg-gray-100 transition-colors`}
    >
      <div className="flex items-start justify-between">
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 mb-1">
            <span className="font-medium text-sm truncate">{project.name}</span>
            <span
              className={`px-2 py-0.5 rounded text-xs font-medium ${getTypeColor(project.type)}`}
            >
              {project.type.replace('_', ' ')}
            </span>
          </div>

          {project.lastCommitMessage && (
            <p className="text-xs text-gray-600 truncate mb-1">
              {project.lastCommitMessage}
            </p>
          )}

          <div className="flex items-center gap-3 text-xs text-gray-500">
            <span>{formatTimeAgo(project.lastUpdated)}</span>

            {project.updateUrgency && (
              <span
                className={`px-2 py-0.5 rounded ${getUrgencyColor(project.updateUrgency)}`}
              >
                {project.updateUrgency}
              </span>
            )}

            <span className="flex items-center gap-1">
              {getDeploymentIcon(project.deploymentStatus)}
              {project.deploymentStatus.replace('_', ' ')}
            </span>
          </div>
        </div>
      </div>
    </motion.div>
  );
};

export default ProjectUpdates;
