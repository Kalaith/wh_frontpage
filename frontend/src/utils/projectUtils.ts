import type { Project } from '../types/projects';

export function getProjectUrl(project: Project): string {
  if (project.deployment?.deployAs) {
    return `${project.deployment.deployAs}/`;
  }
  return project.path || '#';
}

export function getDeploymentBadgeClass(project: Project): string {
  if (!project.deployment) return 'badge-other';
  
  const type = project.deployment.type;
  const badgeMap: Record<string, string> = {
    'Static': 'badge-static',
    'React': 'badge-react',
    'FullStack': 'badge-fullstack',
    'PHP': 'badge-php'
  };
  
  return badgeMap[type] || 'badge-other';
}

export function getStageBadgeClass(project: Project): string {
  if (!project.stage) return 'stage-other';
  
  const stageMap: Record<string, string> = {
    'Static': 'stage-static',
    'React': 'stage-react',
    'Backend': 'stage-backend',
    'Auth': 'stage-auth'
  };
  
  return stageMap[project.stage] || 'stage-other';
}

export function getStatusBadgeClass(project: Project): string {
  if (!project.status) return 'status-unknown';
  
  const statusMap: Record<string, string> = {
    'non-working': 'status-non-working',
    'MVP': 'status-mvp',
    'fully-working': 'status-fully-working'
  };
  
  return statusMap[project.status] || 'status-unknown';
}

export function getStatusBadgeText(project: Project): string {
  if (!project.status) return 'Unknown';
  
  const statusTextMap: Record<string, string> = {
    'non-working': '🚧 Non-Working',
    'MVP': '⚡ MVP',
    'fully-working': '✅ Fully Working'
  };
  
  return statusTextMap[project.status] || project.status;
}
