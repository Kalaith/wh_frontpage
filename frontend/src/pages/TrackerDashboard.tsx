import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import StatsGrid from '../components/tracker/StatsGrid';
import RequestCard from '../components/tracker/RequestCard';
import ActivityFeed from '../components/tracker/ActivityFeed';
import { useProjects } from '../hooks/useProjectsQuery';
import { useTrackerStats, useFeatureRequests, useActivityFeed } from '../hooks/useTrackerQuery';

const TrackerDashboard: React.FC = () => {
  const [selectedProjectId, setSelectedProjectId] = useState<number | null>(null);

  const { data: projectsData, isLoading: projectsLoading } = useProjects();
  const { data: trackerStats, isLoading: statsLoading, error: statsError } = useTrackerStats();
  const { data: topRequests, isLoading: requestsLoading } = useFeatureRequests({ 
    sort_by: 'votes', 
    sort_direction: 'desc', 
    limit: 3,
    project_id: selectedProjectId || undefined
  });
  const { data: recentActivity, isLoading: activityLoading } = useActivityFeed(5, selectedProjectId || undefined);

  // Debug logging
  console.log('TrackerDashboard Debug:', {
    projectsData,
    projectsLoading,
    trackerStats,
    statsLoading,
    selectedProjectId,
    topRequests,
    requestsLoading,
    recentActivity,
    activityLoading
  });

  const isLoading = statsLoading;
  const error = statsError;

  // Get stats from tracker API (including project count)
  const totalProjects = trackerStats?.projects?.total || 0;
  const totalRequests = trackerStats?.feature_requests?.total || 0;
  const openRequests = trackerStats?.feature_requests?.open || 0;
  const completedRequests = trackerStats?.feature_requests?.completed || 0;

  if (isLoading) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="text-center py-12">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-lg text-gray-600">Loading tracker data...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="text-center py-12">
          <div className="text-red-500 mb-4">❌ Error loading tracker data</div>
          <p className="text-gray-600 mb-4">{error.message}</p>
          <button
            onClick={() => window.location.reload()}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  const selectedProject = selectedProjectId && projectsData
    ? Object.values(projectsData.groups || {})
        .flatMap(group => group.projects)
        .find(p => p.id === selectedProjectId)
    : null;

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Project Selection */}
      <div className="mb-8">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Select a Project</h3>
          {projectsLoading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto mb-2"></div>
              <p className="text-gray-500">Loading projects...</p>
            </div>
          ) : !projectsData ? (
            <div className="text-center py-8">
              <p className="text-gray-500">No projects found</p>
            </div>
          ) : (
            <div className="space-y-4">
              <div className="flex flex-wrap gap-2">
                <button
                  type="button"
                  onClick={() => {
                    console.log('All Projects clicked, setting selectedProjectId to null');
                    setSelectedProjectId(null);
                  }}
                  className={`px-4 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer ${
                    selectedProjectId === null
                      ? 'bg-blue-600 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  All Projects
                </button>
                {projectsData && Object.values(projectsData.groups || {}).flatMap(group => group.projects).map((project) => (
                  <button
                    key={project.id}
                    type="button"
                    onClick={() => {
                      console.log(`Project clicked: ${project.title} (ID: ${project.id})`);
                      setSelectedProjectId(project.id);
                    }}
                    className={`px-4 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer ${
                      selectedProjectId === project.id
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    }`}
                  >
                    {project.title}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Project Context */}
      {selectedProject && (
        <div className="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
          <h2 className="font-semibold text-blue-900">
            Current Project: {selectedProject.title}
          </h2>
          <p className="text-sm text-blue-700 mt-1">
            Feature requests and development tracking for {selectedProject.title}
          </p>
        </div>
      )}
      
      {/* Stats Grid */}
      <div className="mb-8">
        <StatsGrid
          totalProjects={totalProjects}
          totalRequests={totalRequests}
          openRequests={openRequests}
          completedRequests={completedRequests}
        />
      </div>
      
      {/* Content Grid */}
      <div className="grid gap-8 lg:grid-cols-2">
        {/* Most Voted Requests */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-xl font-semibold text-gray-900">
              Most Voted Requests
            </h2>
            <Link 
              to="/tracker/requests" 
              className="text-sm text-blue-600 hover:text-blue-700 font-medium"
            >
              View All →
            </Link>
          </div>
          {requestsLoading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto mb-2"></div>
              <p className="text-gray-500">Loading requests...</p>
            </div>
          ) : topRequests && topRequests.length > 0 ? (
            <div className="space-y-4">
              {topRequests.map((request) => (
                <RequestCard
                  key={request.id}
                  title={request.title}
                  description={request.description}
                  votes={request.votes}
                  status={request.status}
                  priority={request.priority}
                  category={request.category}
                  tags={request.tags}
                  date={request.created_at}
                />
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <div className="text-gray-400 text-4xl mb-4">📋</div>
              <p className="text-gray-500 mb-2">No feature requests yet.</p>
              <Link to="/tracker/requests" className="text-blue-600 hover:text-blue-700 font-medium">
                Be the first to submit one!
              </Link>
            </div>
          )}
        </div>

        {/* Recent Activity */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <div className="mb-6">
            <h2 className="text-xl font-semibold text-gray-900">
              Recent Activity
            </h2>
            {selectedProject && (
              <p className="text-sm text-gray-500 mt-1">
                {selectedProject.title}
              </p>
            )}
          </div>
          {activityLoading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto mb-2"></div>
              <p className="text-gray-500">Loading activity...</p>
            </div>
          ) : recentActivity && recentActivity.length > 0 ? (
            <ActivityFeed activities={recentActivity} />
          ) : (
            <div className="text-center py-8">
              <div className="text-gray-400 text-4xl mb-4">📊</div>
              <p className="text-gray-500">No recent activity.</p>
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="mt-12">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
          <div className="text-center">
            <h3 className="text-xl font-semibold text-gray-900 mb-4">Contribute to WebHatchery</h3>
            <p className="text-gray-600 mb-6">Help shape the future by submitting feature requests or suggesting new projects</p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                to="/features"
                className="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors font-medium"
              >
                <span className="text-lg mr-2">🥚</span>
                Feature Requests
              </Link>
              <Link
                to="/tracker/suggestions"
                className="inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors font-medium"
              >
                <span className="text-lg mr-2">💡</span>
                Suggest Project
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

// Updated styling to match site design system
export default TrackerDashboard;