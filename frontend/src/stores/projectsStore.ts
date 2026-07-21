/**
 * Projects Store using Zustand
 * Manages projects data and state throughout the application
 */
import { create } from 'zustand';
import type { ProjectsData, Project } from '../types/projects';
import { StoreError } from '../types/store';
import api from '../api/api';
import { getAllProjects } from '../utils/projectUtils';
import { getErrorMessage } from '../utils/errorHandling';

// Projects state interface
interface ProjectsState {
  projectsData: ProjectsData | null;
  isLoading: boolean;
  error: StoreError | null;
  lastFetched: number | null;
}

// Projects actions interface
interface ProjectsActions {
  // Data fetching (read-only: the catalog is owned by project_roost)
  fetchProjects: () => Promise<ProjectsData>;
  getProjectsData: () => Promise<ProjectsData>;

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
        getErrorMessage(apiResponse.error, 'Failed to fetch projects from API')
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
