import { useQuery } from '@tanstack/react-query';
import { ProjectUpdateApi } from '../api/projectUpdateApi';
import { getErrorMessage } from '../utils/errorHandling';
import type { ProjectUpdate } from '../types/projectUpdates';

export const useProjectUpdates = () => {
  return useQuery({
    queryKey: ['projectUpdates'],
    queryFn: async () => {
      const response = await ProjectUpdateApi.getAllUpdates();
      if (!response.success) {
        throw new Error(
          getErrorMessage(response.error, 'Unable to load project updates')
        );
      }
      return (response.data ?? [])
        .filter(
          (project): project is ProjectUpdate & { lastUpdated: string } =>
            Number.isFinite(Date.parse(project.lastUpdated ?? ''))
        )
        .sort(
          (a, b) => Date.parse(b.lastUpdated) - Date.parse(a.lastUpdated)
        );
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: 10 * 60 * 1000, // Refetch every 10 minutes
  });
};

export const useRecentProjectUpdates = () => {
  return useQuery({
    queryKey: ['projectUpdates', 'recent'],
    queryFn: async () => {
      const response = await ProjectUpdateApi.getRecentUpdates();
      return response.success ? response.data : null;
    },
    staleTime: 2 * 60 * 1000, // 2 minutes
    refetchInterval: 5 * 60 * 1000, // Refetch every 5 minutes
  });
};

export const useProjectUpdateStats = () => {
  return useQuery({
    queryKey: ['projectUpdates', 'stats'],
    queryFn: async () => {
      const response = await ProjectUpdateApi.getStatistics();
      return response.success ? response.data : null;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: 10 * 60 * 1000, // Refetch every 10 minutes
  });
};

export const useProjectsNeedingAttention = () => {
  return useQuery({
    queryKey: ['projectUpdates', 'attention'],
    queryFn: async () => {
      const response = await ProjectUpdateApi.getProjectsNeedingAttention();
      return response.success ? response.data : null;
    },
    staleTime: 1 * 60 * 1000, // 1 minute
    refetchInterval: 2 * 60 * 1000, // Refetch every 2 minutes
  });
};
