import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '../api/api';
import type { Project, ProjectsData } from '../types/projects';
import { getErrorMessage } from '../utils/errorHandling';

// Query keys
export const projectKeys = {
  all: ['projects'] as const,
  lists: () => [...projectKeys.all, 'list'] as const,
  list: (filters: string) => [...projectKeys.lists(), { filters }] as const,
  details: () => [...projectKeys.all, 'detail'] as const,
  detail: (id: number) => [...projectKeys.details(), id] as const,
  byGroup: (group: string) => [...projectKeys.all, 'group', group] as const,
};

// Projects queries
export const useProjects = () => {
  return useQuery({
    queryKey: projectKeys.lists(),
    queryFn: async () => {
      const response = await api.getProjects();
      if (!response.success) {
        throw new Error(
          getErrorMessage(response.error, 'Failed to fetch projects')
        );
      }
      return response.data as ProjectsData;
    },
  });
};

export const useHomepageProjects = () => {
  return useQuery({
    queryKey: [...projectKeys.all, 'homepage'],
    queryFn: async () => {
      const response = await api.getHomepageProjects();
      if (!response.success) {
        throw new Error(
          getErrorMessage(response.error, 'Failed to fetch homepage projects')
        );
      }
      return response.data as ProjectsData;
    },
  });
};

export const useProjectsByGroup = (group: string) => {
  return useQuery({
    queryKey: projectKeys.byGroup(group),
    queryFn: async () => {
      const response = await api.getProjectsByGroup(group);
      if (!response.success) {
        throw new Error(
          getErrorMessage(response.error, 'Failed to fetch projects by group')
        );
      }
      return response.data as Project[];
    },
    enabled: !!group,
  });
};

// Project mutations
export const useCreateProject = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (projectData: Partial<Project>) => {
      const response = await api.createProject(projectData);
      if (!response.success) {
        throw new Error(
          getErrorMessage(response.error, 'Failed to create project')
        );
      }
      return response.data as Project;
    },
    onSuccess: () => {
      // Invalidate and refetch projects
      queryClient.invalidateQueries({ queryKey: projectKeys.all });
    },
  });
};

export const useUpdateProject = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({
      id,
      data,
    }: {
      id: number;
      data: Partial<Project>;
    }) => {
      const response = await api.updateProject(id, data);
      if (!response.success) {
        throw new Error(
          getErrorMessage(response.error, 'Failed to update project')
        );
      }
      return response.data as Project;
    },
    onSuccess: () => {
      // Invalidate and refetch projects
      queryClient.invalidateQueries({ queryKey: projectKeys.all });
    },
  });
};

export const useDeleteProject = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      const response = await api.deleteProject(id);
      if (!response.success) {
        throw new Error(
          getErrorMessage(response.error, 'Failed to delete project')
        );
      }
      return response;
    },
    onSuccess: () => {
      // Invalidate and refetch projects
      queryClient.invalidateQueries({ queryKey: projectKeys.all });
    },
  });
};
