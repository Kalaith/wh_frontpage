import type { Project } from '../types/projects';

export const groupProjectsByName = (projects: Project[]) => {
  const groups: Record<string, Project[]> = {};
  projects.forEach(p => {
    const g = p.group_name || 'other';
    if (!groups[g]) groups[g] = [];
    groups[g].push(p);
  });

  // sort projects within groups by title
  Object.keys(groups).forEach(k => {
    groups[k] = groups[k]
      .slice()
      .sort((a, b) => (a.title || '').localeCompare(b.title || ''));
  });

  return groups;
};

export default groupProjectsByName;
