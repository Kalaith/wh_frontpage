import { useQuery } from '@tanstack/react-query';
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
