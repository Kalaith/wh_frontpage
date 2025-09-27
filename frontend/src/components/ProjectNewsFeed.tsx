import React from 'react';
import { motion } from 'framer-motion';
import { useRecentActivity } from '../hooks/useNewsFeed';
import type { NewsItem } from '../types/newsFeed';

const ProjectNewsFeed: React.FC = () => {
  const { data: activityData, isLoading, error } = useRecentActivity(3); // Last 3 days

  if (isLoading) {
    return (
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-semibold mb-4">Recent Activity</h3>
        <div className="animate-pulse space-y-3">
          {[...Array(3)].map((item, index) => (
            <div key={index} className="flex space-x-3">
              <div className="w-8 h-8 bg-gray-200 rounded-full"></div>
              <div className="flex-1">
                <div className="h-4 bg-gray-200 rounded mb-2"></div>
                <div className="h-3 bg-gray-200 rounded w-3/4"></div>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-semibold mb-4">Recent Activity</h3>
        <div className="text-red-600 text-sm">
          Unable to load activity feed
        </div>
      </div>
    );
  }

  const recentActivity = activityData?.data ?? [];

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="bg-white rounded-lg shadow-sm border p-6"
    >
      <h3 className="text-lg font-semibold mb-4 flex items-center gap-2">
        <span className="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
        Recent Activity
      </h3>

      {recentActivity.length > 0 ? (
        <div className="space-y-4">
          {recentActivity.slice(0, 8).map((item) => (
            <NewsItemCard key={item.id} item={item} />
          ))}
        </div>
      ) : (
        <div className="text-gray-500 text-sm text-center py-8">
          No recent activity in the last 3 days
        </div>
      )}
    </motion.div>
  );
};

interface NewsItemCardProps {
  item: NewsItem;
}

const NewsItemCard: React.FC<NewsItemCardProps> = ({ item }) => {
  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'code_update': return '💻';
      case 'deployment': return '🚀';
      case 'status_change': return '📊';
      default: return '📝';
    }
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'code_update': return 'bg-blue-100 text-blue-800';
      case 'deployment': return 'bg-green-100 text-green-800';
      case 'status_change': return 'bg-purple-100 text-purple-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getProjectTypeColor = (type: string) => {
    switch (type) {
      case 'frontpage': return 'bg-blue-50 text-blue-700';
      case 'apps': return 'bg-green-50 text-green-700';
      case 'game_apps': return 'bg-purple-50 text-purple-700';
      default: return 'bg-gray-50 text-gray-700';
    }
  };

  const formatTimeAgo = (timestamp: string) => {
    const date = new Date(timestamp);
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

  return (
    <motion.div
      initial={{ opacity: 0, x: -20 }}
      animate={{ opacity: 1, x: 0 }}
      className="flex items-start space-x-3 p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors"
    >
      <div className="flex-shrink-0">
        <div className="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-sm">
          {getTypeIcon(item.type)}
        </div>
      </div>

      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2 mb-1">
          <span className="font-medium text-sm text-gray-900">{item.projectName}</span>
          <span className={`px-2 py-0.5 rounded text-xs font-medium ${getProjectTypeColor(item.projectType)}`}>
            {item.projectType.replace('_', ' ')}
          </span>
          <span className={`px-2 py-0.5 rounded text-xs font-medium ${getTypeColor(item.type)}`}>
            {item.type.replace('_', ' ')}
          </span>
        </div>

        <p className="text-sm text-gray-700 mb-2">
          {item.message}
        </p>

        <div className="flex items-center gap-3 text-xs text-gray-500">
          <span>{formatTimeAgo(item.timestamp)}</span>

          {item.metadata.branch && (
            <span className="flex items-center gap-1">
              <span className="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
              {item.metadata.branch}
            </span>
          )}

          {item.metadata.gitCommit && (
            <span className="font-mono bg-gray-100 px-2 py-0.5 rounded">
              {item.metadata.gitCommit.substring(0, 7)}
            </span>
          )}
        </div>
      </div>
    </motion.div>
  );
};

export default ProjectNewsFeed;