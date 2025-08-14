import type { Project } from '../types/projects';

export function getProjectUrl(project: Project): string {
  if (project.deployment?.deployAs) {
    return `${project.deployment.deployAs}/`;
  }
  return project.path || '#';
}

export function getDeploymentBadgeClass(project: Project): string {
  if (!project.deployment) return 'badge-other';
  
  const type = project.deployment.type ?? '';
  const badgeMap: Record<string, string> = {
    'Static': 'badge-static',
    'React': 'badge-react',
    'FullStack': 'badge-fullstack',
    'PHP': 'badge-php'
  };
  
  return (type && badgeMap[type]) ? badgeMap[type] : 'badge-other';
}

export function getStageBadgeClass(project: Project): string {
  if (!project.stage) return 'stage-other';
  
  const stage = project.stage ?? '';
  const stageMap: Record<string, string> = {
    'Static': 'stage-static',
    'React': 'stage-react',
    'Backend': 'stage-backend',
    'Auth': 'stage-auth'
  };
  
  return (stage && stageMap[stage]) ? stageMap[stage] : 'stage-other';
}

export function getStatusBadgeClass(project: Project): string {
  if (!project.status) return 'status-unknown';
  
  const status = project.status ?? '';
  const statusMap: Record<string, string> = {
    'non-working': 'status-non-working',
    'MVP': 'status-mvp',
    'fully-working': 'status-fully-working'
  };
  
  return (status && statusMap[status]) ? statusMap[status] : 'status-unknown';
}

export function getStatusBadgeText(project: Project): string {
  if (!project.status) return 'Unknown';
  
  const status = project.status ?? '';
  const statusTextMap: Record<string, string> = {
    'non-working': '🚧 Non-Working',
    'MVP': '⚡ MVP',
    'fully-working': '✅ Fully Working'
  };
  
  return (status && statusTextMap[status]) ? statusTextMap[status] : project.status;
}
