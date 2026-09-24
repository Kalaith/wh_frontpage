import type { Project } from '../types/projects';

export function getVisitorStatus(status: string): {
  label: string;
  tone: string;
} {
  switch (status.trim().toLowerCase()) {
    case 'complete':
    case 'completed':
    case 'published':
    case 'production':
    case 'fully-working':
    case 'fully working':
    case 'released':
      return { label: 'Released', tone: 'released' };
    case 'mvp':
    case 'strong':
    case 'prototype':
    case 'in development':
      return { label: 'Prototype', tone: 'prototype' };
    case 'concept':
    case 'planning':
    case 'watch':
    case 'risk':
      return { label: 'Concept', tone: 'concept' };
    case 'non-working':
      return { label: 'In progress', tone: 'concept' };
    default:
      return { label: status || 'Experiment', tone: 'concept' };
  }
}

export function getProjectCategory(project: Project, groupName = ''): string {
  const context = `${project.type ?? ''} ${project.group_name ?? ''} ${groupName}`;
  if (/design/i.test(context)) return 'Design';
  if (/fiction|writing/i.test(context)) return 'Writing';
  if (/game/i.test(context)) return 'Game';
  if (/template/i.test(context)) return 'Template';
  const description = `${project.display_name ?? project.title} ${project.description}`;
  if (/\b(tabletop|d&d|campaigns?)\b/i.test(description)) return 'Tabletop';
  if (/\b(story|stories|writing|writers?|novels?|books?)\b/i.test(description))
    return 'Writing';
  if (/\b(learn\w*|language|study|education)\b/i.test(description))
    return 'Learning';
  if (/\b(prompts?|art|creative|images?|design|music)\b/i.test(description))
    return 'Creative tool';
  if (/\bsimulat\w*/i.test(description)) return 'Simulation';
  return 'Experiment';
}
