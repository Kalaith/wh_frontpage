import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ProjectHealthApi } from '../api/projectHealthApi';
import type { ApiResponse } from '../types/common';

function healthData<T>(response: ApiResponse<T>): T {
  if (!response.success || response.data == null) {
    const message =
      typeof response.error === 'string'
        ? response.error
        : response.error?.message;
    throw new Error(
      message ?? response.message ?? 'Health data is unavailable'
    );
  }
  return response.data;
}

export const useSystemHealth = () => {
  return useQuery({
    queryKey: ['projectHealth', 'system'],
    queryFn: async () => {
      const response = await ProjectHealthApi.getSystemHealth();
      return healthData(response);
    },
    staleTime: 3 * 60 * 1000, // 3 minutes
    refetchInterval: 10 * 60 * 1000, // Refetch every 10 minutes
  });
};

export const useHealthSummary = () => {
  return useQuery({
    queryKey: ['projectHealth', 'summary'],
    queryFn: async () => {
      const response = await ProjectHealthApi.getHealthSummary();
      return healthData(response);
    },
    staleTime: 2 * 60 * 1000, // 2 minutes
    refetchInterval: 5 * 60 * 1000, // Refetch every 5 minutes
  });
};

export const useProjectHealth = (projectName: string) => {
  return useQuery({
    queryKey: ['projectHealth', 'project', projectName],
    queryFn: async () => {
      const response = await ProjectHealthApi.getProjectHealth(projectName);
      return healthData(response);
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    enabled: !!projectName, // Only run if projectName is provided
  });
};

export const useCriticalProjects = () => {
  return useQuery({
    queryKey: ['projectHealth', 'critical'],
    queryFn: async () => {
      const response = await ProjectHealthApi.getCriticalProjects();
      return healthData(response);
    },
    staleTime: 1 * 60 * 1000, // 1 minute
    refetchInterval: 2 * 60 * 1000, // Refetch every 2 minutes
  });
};

export const useHealthRecommendations = () => {
  return useQuery({
    queryKey: ['projectHealth', 'recommendations'],
    queryFn: async () => {
      const response = await ProjectHealthApi.getRecommendations();
      return healthData(response);
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: 10 * 60 * 1000, // Refetch every 10 minutes
  });
};

export const useRunHealthCheck = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async () => {
      const response = await ProjectHealthApi.runHealthCheck();
      return healthData(response);
    },
    onSuccess: () => {
      // Invalidate all health-related queries to refresh data
      queryClient.invalidateQueries({ queryKey: ['projectHealth'] });
    },
  });
};
