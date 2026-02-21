import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ProjectHealthApi } from '../api/projectHealthApi';

export const useSystemHealth = () => {
  return useQuery({
    queryKey: ['projectHealth', 'system'],
    queryFn: async () => {
      const response = await ProjectHealthApi.getSystemHealth();
      return response.success ? response.data : null;
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
      return response.success ? response.data : null;
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
      return response.success ? response.data : null;
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
      return response.success ? response.data : null;
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
      return response.success ? response.data : null;
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
      return response.success ? response.data : null;
    },
    onSuccess: () => {
      // Invalidate all health-related queries to refresh data
      queryClient.invalidateQueries({ queryKey: ['projectHealth'] });
    },
  });
};
