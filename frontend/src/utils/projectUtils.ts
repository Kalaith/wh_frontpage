import type { Project, ProjectsData } from '../types/projects';

export function getProjectUrl(project: Project): string {
  if (project.deployment?.deployAs) {
    return `${project.deployment.deployAs}/`;
  }
  return project.path || '#';
}

/**
 * Get all projects as a flat array from ProjectsData
 */
export function getAllProjects(data: ProjectsData | null | undefined): Project[] {
  if (!data) return [];
  
  // Use the flat projects array if available (new backend structure)
  if (data.projects && Array.isArray(data.projects)) {
    return data.projects;
  }
  
  // Fallback to extracting from groups (legacy compatibility)
  if (data.groups) {
    const projects: Project[] = [];
    Object.values(data.groups).forEach(group => {
      if (group.projects && Array.isArray(group.projects)) {
        projects.push(...group.projects);
      }
    });
    return projects;
  }
  
  return [];
}

/**
 * Get project count from ProjectsData
 */
export function getProjectCount(data: ProjectsData | null | undefined): number {
  return getAllProjects(data).length;
}

/**
 * Get grouped projects object from ProjectsData
 */
export function getGroupedProjects(data: ProjectsData | null | undefined): Record<string, Project[]> {
  if (!data) return {};
  
  // Use the grouped object if available (new backend structure)
  if (data.grouped && typeof data.grouped === 'object') {
    return data.grouped;
  }
  
  // Fallback to extracting from groups (legacy compatibility)
  if (data.groups) {
    const grouped: Record<string, Project[]> = {};
    Object.entries(data.groups).forEach(([groupName, group]) => {
      if (group.projects && Array.isArray(group.projects)) {
        grouped[groupName] = group.projects;
      }
    });
    return grouped;
  }
  
  return {};
}

/**
 * Get projects by specific group
 */
export function getProjectsByGroup(data: ProjectsData | null | undefined, groupName: string): Project[] {
  const grouped = getGroupedProjects(data);
  return grouped[groupName] || [];
}

export function getDeploymentBadgeClass(project: Project): string {
  if (!project.deployment) return 'badge-other';

  const type = project.deployment.type ?? '';
  const badgeMap: Record<string, string> = {
    Static: 'badge-static',
    React: 'badge-react',
    FullStack: 'badge-fullstack',
    PHP: 'badge-php',
  };

  return type && badgeMap[type] ? badgeMap[type] : 'badge-other';
}

export function getStageBadgeClass(project: Project): string {
  if (!project.stage) return 'stage-other';

  const stage = project.stage ?? '';
  const stageMap: Record<string, string> = {
    Static: 'stage-static',
    React: 'stage-react',
    Backend: 'stage-backend',
    Auth: 'stage-auth',
  };

  return stage && stageMap[stage] ? stageMap[stage] : 'stage-other';
}

export function getStatusBadgeClass(project: Project): string {
  if (!project.status) return 'status-unknown';

  const status = project.status ?? '';
  const statusMap: Record<string, string> = {
    'non-working': 'status-non-working',
    MVP: 'status-mvp',
    'fully-working': 'status-fully-working',
  };

  return status && statusMap[status] ? statusMap[status] : 'status-unknown';
}

export function getStatusBadgeText(project: Project): string {
  if (!project.status) return 'Unknown';

  const status = project.status ?? '';
  const statusTextMap: Record<string, string> = {
    'non-working': '🚧 Non-Working',
    MVP: '⚡ MVP',
    'fully-working': '✅ Fully Working',
  };

  return status && statusTextMap[status]
    ? statusTextMap[status]
    : project.status;
}
