/**
 * Projects Store using Zustand
 * Manages projects data and state throughout the application
 */
import { create } from 'zustand';
import type { ProjectsData, Project } from '../types/projects';
import { StoreError } from '../types/store';
import api from '../api/api';
import { getAllProjects } from '../utils/projectUtils';

// Projects state interface
interface ProjectsState {
  projectsData: ProjectsData | null;
  isLoading: boolean;
  error: StoreError | null;
  lastFetched: number | null;
}

// Projects actions interface
interface ProjectsActions {
  // Data fetching
  fetchProjects: () => Promise<ProjectsData>;
  getProjectsData: () => Promise<ProjectsData>;

  // CRUD operations
  createProject: (projectData: Partial<ProjectsData>) => Promise<Project>;
  updateProject: (projectId: number, projectData: Partial<ProjectsData>) => Promise<Project>;
  deleteProject: (projectId: number) => Promise<void>;

  // Cache management
  clearCache: () => void;
  refreshProjects: () => Promise<void>;

  // Error handling
  clearError: () => void;
  setError: (error: StoreError | null) => void;

  // Loading state
  setLoading: (loading: boolean) => void;

  // Utilities
  getFlattenedProjects: () => Project[];
}

type ProjectsStore = ProjectsState & ProjectsActions;

const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

export const useProjectsStore = create<ProjectsStore>((set, get) => ({
  // Initial state
  projectsData: null,
  isLoading: false,
  error: null,
  lastFetched: null,

  // Data fetching
  fetchProjects: async () => {
    const state = get();

    // Return cached data if it's still fresh
    if (
      state.projectsData &&
      state.lastFetched &&
      Date.now() - state.lastFetched < CACHE_DURATION
    ) {
      return state.projectsData;
    }

    set({ isLoading: true, error: null });

    try {
      const apiResponse = await api.getProjects();

      if (apiResponse.success && apiResponse.data) {
        const projectsData = apiResponse.data;
        set({
          projectsData,
          isLoading: false,
          error: null,
          lastFetched: Date.now(),
        });
        return projectsData;
      }

      throw new Error(
        apiResponse.error?.message || 'Failed to fetch projects from API'
      );
    } catch (error) {
      const projectsError: StoreError = {
        code: 'FETCH_FAILED',
        message:
          error instanceof Error ? error.message : 'Failed to fetch projects',
      };
      console.error('Error loading projects data:', error);
      set({
        error: projectsError,
        isLoading: false,
        projectsData: null,
        lastFetched: null,
      });
      throw error;
    }
  },

  // Alias for fetchProjects to maintain compatibility
  getProjectsData: async () => {
    return await get().fetchProjects();
  },

  // CRUD operations
  createProject: async (projectData: Partial<ProjectsData>) => {
    set({ isLoading: true, error: null });

    try {
      const response = await api.createProject(projectData);
      if (response.success && response.data) {
        // Clear cache to force refresh
        set({
          projectsData: null,
          lastFetched: null,
          isLoading: false,
        });
        return response.data;
      }
      throw new Error(response.error?.message || 'Failed to create project');
    } catch (error) {
      const projectsError: StoreError = {
        code: 'CREATE_FAILED',
        message:
          error instanceof Error ? error.message : 'Failed to create project',
      };
      set({ error: projectsError, isLoading: false });
      throw error;
    }
  },

  updateProject: async (projectId: number, projectData: Partial<ProjectsData>) => {
    set({ isLoading: true, error: null });

    try {
      const response = await api.updateProject(projectId, projectData);
      if (response.success && response.data) {
        // Clear cache to force refresh
        set({
          projectsData: null,
          lastFetched: null,
          isLoading: false,
        });
        return response.data;
      }
      throw new Error(response.error?.message || 'Failed to update project');
    } catch (error) {
      const projectsError: StoreError = {
        code: 'UPDATE_FAILED',
        message:
          error instanceof Error ? error.message : 'Failed to update project',
      };
      set({ error: projectsError, isLoading: false });
      throw error;
    }
  },

  deleteProject: async (projectId: number) => {
    set({ isLoading: true, error: null });

    try {
      const response = await api.deleteProject(projectId);
      if (response.success) {
        // Clear cache to force refresh
        set({
          projectsData: null,
          lastFetched: null,
          isLoading: false,
        });
        return;
      }
      throw new Error(response.error?.message || 'Failed to delete project');
    } catch (error) {
      const projectsError: StoreError = {
        code: 'DELETE_FAILED',
        message:
          error instanceof Error ? error.message : 'Failed to delete project',
      };
      set({ error: projectsError, isLoading: false });
      throw error;
    }
  },

  // Cache management
  clearCache: () => {
    set({
      projectsData: null,
      lastFetched: null,
      error: null,
    });
  },

  refreshProjects: async () => {
    // Force refresh by clearing cache first
    get().clearCache();
    await get().fetchProjects();
  },

  // Error handling
  clearError: () => set({ error: null }),
  setError: (error: StoreError | null) => set({ error }),

  // Loading state
  setLoading: (loading: boolean) => set({ isLoading: loading }),

  // Utilities
  getFlattenedProjects: () => {
    const { projectsData } = get();
    return getAllProjects(projectsData);
  },
}));

// Selector hooks for specific data
export const useProjects = () => {
  const store = useProjectsStore();
  return {
    ...store,
    projects: store.getFlattenedProjects(),
  };
};

export const useProjectsData = () => {
  return useProjectsStore(state => state.projectsData);
};

export const useProjectsLoading = () => {
  return useProjectsStore(state => state.isLoading);
};

export const useProjectsError = () => {
  return useProjectsStore(state => state.error);
};
