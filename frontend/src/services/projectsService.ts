import type { ProjectsData, Project } from '../types/projects';
import api from '../api/api';
import { getAllProjects } from '../utils/projectUtils';
import { getErrorMessage } from '../utils/errorHandling';

export class ProjectsService {
  private static instance: ProjectsService;
  private projectsData: ProjectsData | null = null;

  private constructor() {}

  public static getInstance(): ProjectsService {
    if (!ProjectsService.instance) {
      ProjectsService.instance = new ProjectsService();
    }
    return ProjectsService.instance;
  }

  public async getProjectsData(): Promise<ProjectsData> {
    if (this.projectsData) {
      return this.projectsData;
    }

    try {
      const apiResponse = await api.getProjects();

      if (apiResponse.success && apiResponse.data) {
        this.projectsData = apiResponse.data;
        return this.projectsData;
      }

      throw new Error(
        getErrorMessage(apiResponse.error, 'Failed to fetch projects from API')
      );
    } catch (error) {
      console.error('Error loading projects data:', error);
      throw error;
    }
  }

  public clearCache(): void {
    this.projectsData = null;
  }

  // Create a project via API and clear cache so callers can re-fetch fresh data
  public async createProject(projectData: Partial<Project>): Promise<Project> {
    const res = await api.createProject(projectData);
    if (res.success && res.data) {
      this.clearCache();
      return res.data;
    }
    throw new Error(getErrorMessage(res.error, 'Failed to create project'));
  }

  // Update a project via API and clear cache
  public async updateProject(
    projectId: number,
    projectData: Partial<Project>
  ): Promise<Project> {
    const res = await api.updateProject(projectId, projectData);
    if (res.success && res.data) {
      this.clearCache();
      return res.data;
    }
    throw new Error(getErrorMessage(res.error, 'Failed to update project'));
  }

  // Delete a project via API and clear cache
  public async deleteProject(projectId: number): Promise<void> {
    const res = await api.deleteProject(projectId);
    if (res.success) {
      this.clearCache();
      return;
    }
    throw new Error(getErrorMessage(res.error, 'Failed to delete project'));
  }

  // Utility to flatten ProjectsData into a simple Project[] list
  public static flattenProjectsData(data: ProjectsData): Project[] {
    return getAllProjects(data);
  }
}
