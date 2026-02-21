import React from 'react';
import { Link } from 'react-router-dom';
import StatsGrid from '../components/tracker/StatsGrid';
import RequestCard from '../components/tracker/RequestCard';
import ActivityFeed from '../components/tracker/ActivityFeed';
import LoadingSpinner from '../components/ui/LoadingSpinner';
import ErrorDisplay from '../components/ui/ErrorDisplay';
import { useProjects } from '../hooks/useProjectsQuery';
import { useTrackerData } from '../hooks/useTrackerData';
import { getAllProjects } from '../utils/projectUtils';

const TrackerDashboard: React.FC = () => {
  const { data: projectsData, isLoading: projectsLoading } = useProjects();

  const {
    selectedProjectIds,
    setSelectedProjectIds,
    stats,
    filteredTopRequests,
    filteredRecentActivity,
    isLoading,
    requestsLoading,
    activityLoading,
    error,
    featureToProjectMap,
  } = useTrackerData();

  // Debug logging
  console.log('TrackerDashboard Debug:', {
    projectsData,
    projectsLoading,
    selectedProjectIds,
    calculatedStats: stats,
    filteredTopRequests,
    requestsLoading,
    filteredRecentActivity,
    activityLoading,
    isLoading,
    error,
  });

  if (isLoading) {
    return <LoadingSpinner message="Loading tracker data..." />;
  }

  if (error) {
    return (
      <ErrorDisplay
        title="❌ Error loading tracker data"
        message={error.message}
        onRetry={() => window.location.reload()}
      />
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Project Selection */}
      <div className="mb-8">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">
            Select a Project
          </h3>
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
                    console.log(
                      'All Projects clicked, clearing selected projects'
                    );
                    setSelectedProjectIds([]);
                  }}
                  className={`px-4 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer ${
                    selectedProjectIds.length === 0
                      ? 'bg-blue-600 text-white'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  All Projects
                </button>
                {getAllProjects(projectsData).map(project => (
                  <button
                    key={project.id}
                    type="button"
                    onClick={() => {
                      const projectId = project.id;
                      if (!projectId) return;

                      console.log(
                        `Project clicked: ${project.title} (ID: ${projectId})`
                      );
                      setSelectedProjectIds(prev => {
                        if (prev.includes(projectId)) {
                          // Remove from selection
                          return prev.filter(id => id !== projectId);
                        } else {
                          // Add to selection
                          return [...prev, projectId];
                        }
                      });
                    }}
                    className={`px-4 py-2 rounded-md text-sm font-medium transition-colors cursor-pointer ${
                      selectedProjectIds.includes(project.id ?? -1)
                        ? 'bg-blue-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    }`}
                  >
                    {project.title}
                    {selectedProjectIds.includes(project.id ?? -1) && (
                      <span className="ml-1 text-xs">✓</span>
                    )}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Stats Grid */}
      <div className="mb-8">
        <StatsGrid
          totalProjects={stats.totalProjects}
          totalRequests={stats.totalRequests}
          openRequests={stats.openRequests}
          completedRequests={stats.completedRequests}
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
              <p className="text-gray-600">Loading requests...</p>
            </div>
          ) : filteredTopRequests && filteredTopRequests.length > 0 ? (
            <div className="space-y-4">
              {filteredTopRequests.map(request => (
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
                  project={request.project}
                />
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <div className="text-gray-400 text-4xl mb-4">📋</div>
              <p className="text-gray-600 mb-2">No feature requests yet.</p>
              <Link
                to="/tracker/requests"
                className="text-blue-600 hover:text-blue-700 font-medium"
              >
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
            <p className="text-sm text-gray-500 mt-1">
              {selectedProjectIds.length === 0
                ? 'All projects'
                : selectedProjectIds.length === 1
                  ? 'Selected project'
                  : `${selectedProjectIds.length} selected projects`}
            </p>
          </div>
          {activityLoading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto mb-2"></div>
              <p className="text-gray-600">Loading activity...</p>
            </div>
          ) : filteredRecentActivity && filteredRecentActivity.length > 0 ? (
            <ActivityFeed
              activities={filteredRecentActivity}
              featureToProjectMap={featureToProjectMap}
            />
          ) : (
            <div className="text-center py-8">
              <div className="text-gray-400 text-4xl mb-4">📊</div>
              <p className="text-gray-600">No recent activity.</p>
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="mt-12">
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
          <div className="text-center">
            <h3 className="text-xl font-semibold text-gray-900 mb-4">
              Contribute to WebHatchery
            </h3>
            <p className="text-gray-600 mb-6">
              Help shape the future by submitting feature requests or suggesting
              new projects
            </p>
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
