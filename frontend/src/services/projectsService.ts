import type { ProjectsData } from '../types/projects';
import api from '../api/api';

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
      
      throw new Error(apiResponse.error?.message || 'Failed to fetch projects from API');
    } catch (error) {
      console.error('Error loading projects data:', error);
      throw error;
    }
  }

  public clearCache(): void {
    this.projectsData = null;
  }
}
