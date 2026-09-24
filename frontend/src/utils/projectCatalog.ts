import type { ProjectsData } from '../types/projects';
import { getProjectDisplayName } from './projectUtils';

export const projectTypes = [
  'Apps',
  'Games',
  'Writing',
  'Design',
  'Templates',
  'Other',
] as const;
export type ProjectType = (typeof projectTypes)[number];

function getType(context: string): ProjectType {
  // Design must precede games: game design documents aren't playable games.
  if (/design/i.test(context)) return 'Design';
  if (/template/i.test(context)) return 'Templates';
  if (/fiction|writing|novel|books?/i.test(context)) return 'Writing';
  if (/game/i.test(context)) return 'Games';
  if (/app|tool|experiment/i.test(context)) return 'Apps';
  return 'Other';
}

export function getProjectCatalog(data?: ProjectsData) {
  return Object.entries(data?.groups ?? {})
    .flatMap(([id, group]) =>
      group.projects.map(project => {
        const canonicalType = getType(project.group_name ?? id);
        const groupType =
          canonicalType === 'Other' ? getType(group.name) : canonicalType;
        const type =
          groupType === 'Other' ? getType(project.type ?? '') : groupType;
        // Use catalog metadata only; prose mentioning React or Rust is not evidence.
        const metadata = `${id} ${group.name} ${project.group_name ?? ''} ${project.type ?? ''} ${project.stage} ${project.deployment?.type ?? ''}`;
        const technologies = ['Rust', 'React', 'Static'].filter(tech =>
          new RegExp(`(?:^|[^a-z])${tech}(?:$|[^a-z])`, 'i').test(metadata)
        );
        return { project, type, technologies };
      })
    )
    .sort((a, b) =>
      getProjectDisplayName(a.project).localeCompare(
        getProjectDisplayName(b.project)
      )
    );
}
