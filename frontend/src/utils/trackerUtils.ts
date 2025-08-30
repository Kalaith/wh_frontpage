/**
 * Tracker data filtering utilities
 * Reusable functions for processing tracker data
 */
import type { FeatureRequest, ActivityItem } from '../api/trackerApi';
import { STATUS_VALUES, LIMITS } from '../constants/app';

/**
 * Creates a mapping of feature request IDs to their projects
 */
export const createFeatureToProjectMap = (featureRequests: FeatureRequest[]) => {
  const map: Record<number, { id: number; title: string; group_name?: string }> = {};
  featureRequests.forEach(request => {
    if (request.id && request.project) {
      map[request.id] = request.project;
    }
  });
  return map;
};

/**
 * Filters feature requests by selected project IDs
 */
export const filterRequestsByProjects = (
  requests: FeatureRequest[],
  selectedProjectIds: number[],
  limit?: number
) => {
  if (selectedProjectIds.length === 0) {
    return limit ? requests.slice(0, limit) : requests;
  }

  const filtered = selectedProjectIds.length > 1
    ? requests.filter(request => 
        request.project?.id && selectedProjectIds.includes(request.project.id)
      )
    : requests;

  return limit ? filtered.slice(0, limit) : filtered;
};

/**
 * Filters activities by selected project IDs using feature-to-project mapping
 */
export const filterActivitiesByProjects = (
  activities: ActivityItem[],
  selectedProjectIds: number[],
  featureToProjectMap: Record<number, { id: number; title: string; group_name?: string }>,
  limit = LIMITS.ACTIVITY_FEED_DISPLAY
) => {
  if (selectedProjectIds.length === 0) {
    return activities.slice(0, limit);
  }

  const filtered = activities.filter(activity => {
    if (activity.reference_type === 'feature_request' && activity.reference_id) {
      const project = featureToProjectMap[activity.reference_id];
      return project && selectedProjectIds.includes(project.id);
    }
    return selectedProjectIds.length === 0;
  });

  return filtered.slice(0, limit);
};

/**
 * Calculates stats for selected projects
 */
export const calculateProjectStats = (
  allFeatureRequests: FeatureRequest[] | undefined,
  selectedProjectIds: number[],
  globalStats?: {
    projects?: { total: number };
    feature_requests?: { total: number; open: number; completed: number };
  }
) => {
  if (!allFeatureRequests || selectedProjectIds.length === 0) {
    return {
      totalProjects: globalStats?.projects?.total ?? 0,
      totalRequests: globalStats?.feature_requests?.total ?? 0,
      openRequests: globalStats?.feature_requests?.open ?? 0,
      completedRequests: globalStats?.feature_requests?.completed ?? 0
    };
  }

  const filteredRequests = allFeatureRequests.filter(request =>
    request.project?.id && selectedProjectIds.includes(request.project.id)
  );

  const openCount = filteredRequests.filter(request =>
    STATUS_VALUES.OPEN.includes(request.status as 'Open' | 'open' | 'pending')
  ).length;

  const completedCount = filteredRequests.filter(request =>
    STATUS_VALUES.COMPLETED.includes(request.status as 'Completed' | 'completed')
  ).length;

  return {
    totalProjects: selectedProjectIds.length,
    totalRequests: filteredRequests.length,
    openRequests: openCount,
    completedRequests: completedCount
  };
};