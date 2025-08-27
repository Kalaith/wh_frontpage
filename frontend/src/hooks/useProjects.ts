import { useEffect, useState, useCallback } from 'react';
import { ProjectsService } from '../services/projectsService';
import type { Project } from '../types/projects';
import { groupProjectsByName } from '../utils';

export const useProjects = () => {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const svc = ProjectsService.getInstance();
      const data = await svc.getProjectsData();
      const flat = ProjectsService.flattenProjectsData(data) as Project[];
      setProjects(flat);
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const createProject = useCallback(async (p: Partial<Project>) => {
    try {
      const svc = ProjectsService.getInstance();
      const created = await svc.createProject(p as any);
      // optimistically add created project
      setProjects(prev => [created as Project, ...prev]);
      return created;
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
      throw err;
    }
  }, []);

  const updateProject = useCallback(
    async (id: number, updates: Partial<Project>) => {
      try {
        const svc = ProjectsService.getInstance();
        const updated = await svc.updateProject(id, updates as any);
        setProjects(prev =>
          prev.map(p => (p.id === id ? (updated as Project) : p))
        );
        return updated;
      } catch (err) {
        setError(err instanceof Error ? err.message : String(err));
        throw err;
      }
    },
    []
  );

  const deleteProject = useCallback(async (id: number) => {
    try {
      const svc = ProjectsService.getInstance();
      await svc.deleteProject(id);
      setProjects(prev => prev.filter(p => p.id !== id));
    } catch (err) {
      setError(err instanceof Error ? err.message : String(err));
      throw err;
    }
  }, []);

  const grouped = groupProjectsByName(projects);

  return {
    projects,
    grouped,
    loading,
    error,
    reload: load,
    createProject,
    updateProject,
    deleteProject,
  };
};

export default useProjects;
