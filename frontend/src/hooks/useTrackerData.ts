/**
 * Custom hook for tracker dashboard data management
 * Consolidates data fetching and filtering logic
 */
import { useState, useMemo } from 'react';
import {
  useTrackerStats,
  useFeatureRequests,
  useActivityFeed,
} from './useTrackerQuery';
import {
  createFeatureToProjectMap,
  filterRequestsByProjects,
  filterActivitiesByProjects,
  calculateProjectStats,
} from '../utils/trackerUtils';
import { LIMITS } from '../constants/app';

export const useTrackerData = () => {
  const [selectedProjectIds, setSelectedProjectIds] = useState<number[]>([]);

  // Fetch base data
  const {
    data: trackerStats,
    isLoading: statsLoading,
    error: statsError,
  } = useTrackerStats();
  const { data: allFeatureRequests } = useFeatureRequests({});

  // Fetch top requests with dynamic parameters
  const { data: topRequests, isLoading: requestsLoading } = useFeatureRequests({
    sort_by: 'votes',
    sort_direction: 'desc',
    limit:
      selectedProjectIds.length > 0
        ? LIMITS.TOP_REQUESTS_FILTERED
        : LIMITS.TOP_REQUESTS_DISPLAY,
    project_id:
      selectedProjectIds.length === 1 ? selectedProjectIds[0] : undefined,
  });

  // Fetch activity feed with dynamic parameters
  const { data: allRecentActivity, isLoading: activityLoading } =
    useActivityFeed(
      selectedProjectIds.length > 1
        ? LIMITS.ACTIVITY_FEED_FILTERED
        : LIMITS.ACTIVITY_FEED_DISPLAY,
      selectedProjectIds.length === 1 ? selectedProjectIds[0] : undefined
    );

  // Create feature-to-project mapping
  const featureToProjectMap = useMemo(() => {
    if (!allFeatureRequests) return {};
    return createFeatureToProjectMap(allFeatureRequests);
  }, [allFeatureRequests]);

  // Filter data based on selected projects
  const filteredTopRequests = useMemo(() => {
    if (!topRequests) return [];
    return filterRequestsByProjects(
      topRequests,
      selectedProjectIds,
      LIMITS.TOP_REQUESTS_DISPLAY
    );
  }, [topRequests, selectedProjectIds]);

  const filteredRecentActivity = useMemo(() => {
    if (!allRecentActivity) return [];
    return filterActivitiesByProjects(
      allRecentActivity,
      selectedProjectIds,
      featureToProjectMap,
      LIMITS.ACTIVITY_FEED_DISPLAY
    );
  }, [allRecentActivity, selectedProjectIds, featureToProjectMap]);

  // Calculate project-specific stats
  const stats = useMemo(() => {
    return calculateProjectStats(
      allFeatureRequests,
      selectedProjectIds,
      trackerStats
    );
  }, [allFeatureRequests, selectedProjectIds, trackerStats]);

  return {
    // State
    selectedProjectIds,
    setSelectedProjectIds,

    // Computed data
    stats,
    filteredTopRequests,
    filteredRecentActivity,

    // Loading states
    isLoading: statsLoading,
    requestsLoading,
    activityLoading,

    // Error states
    error: statsError,

    // Raw data (for debugging or advanced usage)
    trackerStats,
    allFeatureRequests,
    topRequests,
    allRecentActivity,
    featureToProjectMap,
  };
};
