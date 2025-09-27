import { useQuery } from '@tanstack/react-query';
import { ProjectUpdateApi } from '../api/projectUpdateApi';

export const useProjectUpdates = () => {
  return useQuery({
    queryKey: ['projectUpdates'],
    queryFn: ProjectUpdateApi.getAllUpdates,
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: 10 * 60 * 1000, // Refetch every 10 minutes
  });
};

export const useRecentProjectUpdates = () => {
  return useQuery({
    queryKey: ['projectUpdates', 'recent'],
    queryFn: ProjectUpdateApi.getRecentUpdates,
    staleTime: 2 * 60 * 1000, // 2 minutes
    refetchInterval: 5 * 60 * 1000, // Refetch every 5 minutes
  });
};

export const useProjectUpdateStats = () => {
  return useQuery({
    queryKey: ['projectUpdates', 'stats'],
    queryFn: ProjectUpdateApi.getStatistics,
    staleTime: 5 * 60 * 1000, // 5 minutes
    refetchInterval: 10 * 60 * 1000, // Refetch every 10 minutes
  });
};

export const useProjectsNeedingAttention = () => {
  return useQuery({
    queryKey: ['projectUpdates', 'attention'],
    queryFn: ProjectUpdateApi.getProjectsNeedingAttention,
    staleTime: 1 * 60 * 1000, // 1 minute
    refetchInterval: 2 * 60 * 1000, // Refetch every 2 minutes
  });
};