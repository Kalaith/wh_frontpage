import React from 'react';
import type { ActivityItem } from '../../api/trackerApi';

interface ActivityFeedProps {
  activities?: ActivityItem[];
}

const ActivityFeed: React.FC<ActivityFeedProps> = ({ activities = [] }) => {
  const getActivityColor = (type: string, action: string) => {
    if (type === 'feature_request') {
      return action === 'created' ? 'bg-blue-600' : action === 'completed' ? 'bg-green-500' : 'bg-yellow-500';
    }
    if (type === 'project_suggestion') {
      return action === 'created' ? 'bg-purple-600' : 'bg-indigo-500';
    }
    return 'bg-gray-500';
  };

  if (activities.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        <p>No recent activity.</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {activities.map((activity) => (
        <div key={activity.id} className="flex items-start space-x-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50">
          <div className={`w-2 h-2 rounded-full mt-2 ${getActivityColor(activity.type, activity.action)}`}></div>
          <div className="flex-1 min-w-0">
            <div className="font-medium text-gray-900 dark:text-gray-100">{activity.title}</div>
            {activity.description && (
              <div className="text-sm text-gray-600 dark:text-gray-400 mt-1">{activity.description}</div>
            )}
            <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {activity.created_at_relative} • by {activity.user || 'Anonymous'}
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};

export default ActivityFeed;