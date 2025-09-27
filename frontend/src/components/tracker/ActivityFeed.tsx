import React from 'react';
import type { ActivityItem } from '../../api/trackerApi';

interface ActivityFeedProps {
  activities?: ActivityItem[];
  featureToProjectMap?: Record<number, { id: number; title: string; group_name?: string }>;
}

const ActivityFeed: React.FC<ActivityFeedProps> = ({ activities = [], featureToProjectMap = {} }) => {
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
      {activities.map((activity) => {
        // Get project info if this is a feature request activity
        const project = activity.reference_type === 'feature_request' && activity.reference_id 
          ? featureToProjectMap[activity.reference_id] 
          : null;

        return (
          <div key={activity.id} className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-blue-500 p-4">
            <div className="flex items-start gap-4">
              <div className={`w-3 h-3 rounded-full mt-2 ${getActivityColor(activity.type, activity.action)} flex-shrink-0`}></div>
              <div className="flex-1 min-w-0">
                <div className="flex items-start justify-between gap-2 mb-2">
                  <h4 className="font-semibold text-blue-600 text-sm leading-5">
                    {activity.title}
                  </h4>
                  {project && (
                    <span className="px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full border border-gray-200 flex-shrink-0">
                      {project.title}
                    </span>
                  )}
                </div>
                {activity.description && (
                  <p className="text-sm text-gray-600 mb-2 leading-5">{activity.description}</p>
                )}
                <div className="flex items-center gap-2 text-xs text-gray-500">
                  <span>{activity.created_at_relative}</span>
                  <span className="text-gray-400">•</span>
                  <span>by {activity.user ?? 'Anonymous'}</span>
                  {activity.type && (
                    <>
                      <span className="text-gray-400">•</span>
                      <span className="px-2 py-0.5 bg-gray-100 text-gray-600 rounded border border-gray-200 font-medium">
                        {activity.type.replace('_', ' ')}
                      </span>
                    </>
                  )}
                  {activity.action && (
                    <>
                      <span className="text-gray-400">•</span>
                      <span className="px-2 py-0.5 bg-blue-100 text-blue-700 rounded border border-blue-200 font-medium">
                        {activity.action}
                      </span>
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
};

export default ActivityFeed;