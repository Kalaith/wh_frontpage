/**
 * Stores index - exports all stores and related hooks
 */

// Auth store
export { useAuthStore, useAuth, useAuthInitialization } from './authStore';

// Projects store
export {
  useProjectsStore,
  useProjects,
  useProjectsData,
  useProjectsLoading,
  useProjectsError,
} from './projectsStore';

// Re-export types that are commonly used with stores
export type { AuthState } from '../entities/Auth';
export type { ProjectsData } from '../types/projects';
