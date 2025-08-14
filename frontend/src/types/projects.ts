export interface ProjectRepository {
  type: 'git';
  url: string;
}

export interface Project {
  title: string;
  path?: string;
  description: string;
  stage: string;
  status: string;
  version: string;
  repository?: ProjectRepository;
}

export interface ProjectGroup {
  name: string;
  projects: Project[];
}

export interface ProjectsData {
  version: string;
  description: string;
  groups: Record<string, ProjectGroup>;
}
