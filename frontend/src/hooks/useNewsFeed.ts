import { useQuery } from '@tanstack/react-query';
import { NewsFeedApi } from '../api/newsFeedApi';

export const useNewsFeed = (limit: number = 20) => {
  return useQuery({
    queryKey: ['newsFeed', limit],
    queryFn: async () => {
      const response = await NewsFeedApi.getNewsFeed(limit);
      return response.success ? response.data : null;
    },
    staleTime: 2 * 60 * 1000, // 2 minutes
    refetchInterval: 5 * 60 * 1000, // Refetch every 5 minutes
  });
};

export const useRecentActivity = (days: number = 7) => {
  return useQuery({
    queryKey: ['newsFeed', 'recent', days],
    queryFn: async () => {
      const response = await NewsFeedApi.getRecentActivity(days);
      return response.success ? response.data : null;
    },
    staleTime: 1 * 60 * 1000, // 1 minute
    refetchInterval: 3 * 60 * 1000, // Refetch every 3 minutes
  });
};

export const useActivityStats = () => {
  return useQuery({
    queryKey: ['newsFeed', 'stats'],
    queryFn: async () => {
      const response = await NewsFeedApi.getActivityStats();
      return response.success ? response.data : null;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: 10 * 60 * 1000, // Refetch every 10 minutes
  });
};

export const useProjectChangelog = (
  projectName: string,
  limit: number = 10
) => {
  return useQuery({
    queryKey: ['newsFeed', 'project', projectName, limit],
    queryFn: async () => {
      const response = await NewsFeedApi.getProjectChangelog(
        projectName,
        limit
      );
      return response.success ? response.data : null;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    enabled: !!projectName, // Only run if projectName is provided
  });
};
