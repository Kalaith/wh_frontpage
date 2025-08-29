export interface ProjectRepository {
  url: string;
}

export interface ProjectDeploymentBackend {
  type?: string;
}

export interface ProjectDeployment {
  type?: string;
  requiresBuild?: boolean;
  deployAs?: string;
  packageManager?: string;
  backend?: ProjectDeploymentBackend;
}

export interface Project {
  id?: number;
  title: string;
  path?: string;
  description: string;
  stage: string;
  status: string;
  version: string;
  group_name?: string;
  show_on_homepage?: boolean;
  repository?: ProjectRepository;
  deployment?: ProjectDeployment;
}

export interface ProjectGroup {
  name: string;
  projects: Project[];
}

export interface ProjectsData {
  version: string;
  description: string;
  groups: Record<string, ProjectGroup>;
  projects: Project[];
  grouped: Record<string, Project[]>;
  global?: {
    repository?: {
      name?: string;
      url?: string;
    };
    buildTools?: Record<string, string>;
  };
}
