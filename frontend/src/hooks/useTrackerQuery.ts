import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { trackerApi } from '../api/trackerApi';

// Query keys
export const trackerKeys = {
  all: ['tracker'] as const,
  stats: () => [...trackerKeys.all, 'stats'] as const,
  featureRequests: () => [...trackerKeys.all, 'feature-requests'] as const,
  projectSuggestions: () =>
    [...trackerKeys.all, 'project-suggestions'] as const,
  activity: () => [...trackerKeys.all, 'activity'] as const,
};

// Tracker queries
export const useTrackerStats = () => {
  return useQuery({
    queryKey: trackerKeys.stats(),
    queryFn: () => trackerApi.getStats(),
  });
};

export const useFeatureRequests = (params?: {
  status?: string;
  priority?: string;
  category?: string;
  project_id?: number;
  sort_by?: string;
  sort_direction?: string;
  limit?: number;
}) => {
  return useQuery({
    queryKey: [...trackerKeys.featureRequests(), params],
    queryFn: () => trackerApi.getFeatureRequests(params),
  });
};

export const useProjectSuggestions = (params?: {
  group?: string;
  status?: string;
  sort_by?: string;
  sort_direction?: string;
  limit?: number;
}) => {
  return useQuery({
    queryKey: [...trackerKeys.projectSuggestions(), params],
    queryFn: () => trackerApi.getProjectSuggestions(params),
  });
};

export const useActivityFeed = (limit?: number, projectId?: number) => {
  return useQuery({
    queryKey: [...trackerKeys.activity(), limit, projectId],
    queryFn: () => trackerApi.getActivityFeed(limit, projectId),
  });
};

// Tracker mutations
export const useCreateFeatureRequest = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: {
      title: string;
      description: string;
      category?: string;
      priority?: string;
      tags?: string;
      submitted_by?: string;
    }) => trackerApi.createFeatureRequest(data),
    onSuccess: () => {
      // Invalidate and refetch tracker data
      queryClient.invalidateQueries({
        queryKey: trackerKeys.featureRequests(),
      });
      queryClient.invalidateQueries({ queryKey: trackerKeys.stats() });
      queryClient.invalidateQueries({ queryKey: trackerKeys.activity() });
    },
  });
};

export const useCreateProjectSuggestion = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: {
      name: string;
      description: string;
      group?: string;
      rationale: string;
      submitted_by?: string;
    }) => trackerApi.createProjectSuggestion(data),
    onSuccess: () => {
      // Invalidate and refetch tracker data
      queryClient.invalidateQueries({
        queryKey: trackerKeys.projectSuggestions(),
      });
      queryClient.invalidateQueries({ queryKey: trackerKeys.stats() });
      queryClient.invalidateQueries({ queryKey: trackerKeys.activity() });
    },
  });
};

export const useVote = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: {
      item_type: 'feature_request' | 'project_suggestion';
      item_id: number;
      vote_value: 1 | -1;
    }) => trackerApi.vote(data),
    onSuccess: () => {
      // Invalidate and refetch relevant data
      queryClient.invalidateQueries({
        queryKey: trackerKeys.featureRequests(),
      });
      queryClient.invalidateQueries({
        queryKey: trackerKeys.projectSuggestions(),
      });
      queryClient.invalidateQueries({ queryKey: trackerKeys.stats() });
    },
  });
};
