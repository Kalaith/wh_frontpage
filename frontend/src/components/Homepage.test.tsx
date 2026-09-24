import { render, screen, cleanup } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import { ProjectShowcase } from './ProjectShowcase';
import { LatestUpdates } from './LatestUpdates';
import { useProjectUpdates } from '../hooks/useProjectUpdates';
import type { ProjectsData, Project } from '../types/projects';

vi.mock('../hooks/useProjectUpdates', () => ({ useProjectUpdates: vi.fn() }));
afterEach(() => {
  cleanup();
  localStorage.removeItem('hatchery-density');
});

const makeProject = (title: string, description: string): Project => ({
  title,
  description,
  status: 'MVP',
  stage: 'React',
  version: '1',
  path: `/${title}/`,
});
const app = makeProject('Story Tool', 'Write a fantasy adventure.');
const game = makeProject('Space Game', 'Explore distant planets.');
const data: ProjectsData = {
  version: '1',
  description: '',
  groups: {
    apps: { name: 'Web Applications', projects: [app] },
    games: { name: 'Games & Game Design', projects: [game] },
  },
  projects: [app, game],
  grouped: { apps: [app], games: [game] },
};

it('combines group filtering and search, then restores the collection after no results', async () => {
  const user = userEvent.setup();
  render(<ProjectShowcase data={data} />);
  await user.click(screen.getByRole('button', { name: 'Games 1' }));
  expect(
    screen.queryByRole('link', { name: 'Story Tool' })
  ).not.toBeInTheDocument();
  expect(screen.getByRole('link', { name: 'Space Game' })).toHaveAttribute(
    'href',
    '/Space Game/'
  );
  await user.type(screen.getByRole('searchbox'), 'fantasy');
  expect(screen.getByRole('status')).toHaveTextContent('0 of 2 projects');
  expect(
    screen.getByRole('heading', { name: 'No projects match these filters.' })
  ).toBeInTheDocument();
  await user.click(screen.getByRole('button', { name: 'Clear filters' }));
  expect(screen.getByRole('link', { name: 'Story Tool' })).toBeInTheDocument();
  expect(screen.getByRole('link', { name: 'Space Game' })).toBeInTheDocument();
});

it('combines Rust games with games and intersects type, technology and search filters', async () => {
  const user = userEvent.setup();
  const rustGame = {
    ...makeProject('Rust Space', 'Explore a rusty planet.'),
    stage: 'Rust',
  };
  const catalog = {
    ...data,
    groups: {
      ...data.groups,
      rust_games: { name: 'Rust Games', projects: [rustGame] },
      game_design: {
        name: 'Game Design',
        projects: [makeProject('Space Design', 'Game rules.')],
      },
      fiction: {
        name: 'Fiction Projects',
        projects: [makeProject('Space Novel', 'A novel.')],
      },
    },
  };
  render(<ProjectShowcase data={catalog} />);
  expect(screen.getByRole('heading', { level: 1 })).toHaveTextContent(
    '5 projects'
  );
  expect(screen.getByRole('button', { name: 'All 5' })).toBeInTheDocument();
  expect(screen.getByRole('button', { name: 'Design 1' })).toBeInTheDocument();
  expect(screen.getByRole('button', { name: 'Writing 1' })).toBeInTheDocument();
  await user.click(screen.getByRole('button', { name: 'Games 2' }));
  expect(screen.getAllByRole('article')).toHaveLength(2);
  await user.click(screen.getByRole('button', { name: 'Filters' }));
  await user.click(screen.getByRole('button', { name: 'Rust', exact: true }));
  expect(screen.getAllByRole('article')).toHaveLength(1);
  expect(screen.getByRole('link', { name: 'Rust Space' })).toBeInTheDocument();
  await user.type(screen.getByRole('searchbox'), 'no match');
  expect(screen.getByRole('status')).toHaveTextContent('0 of 5 projects');
  await user.click(screen.getByRole('button', { name: 'Clear filters' }));
  expect(screen.getAllByRole('article')).toHaveLength(5);
  expect(screen.getByRole('button', { name: 'Any' })).toHaveAttribute(
    'aria-pressed',
    'true'
  );
});

it('keeps filtered results and source links when switching density, and remembers the preference', async () => {
  const user = userEvent.setup();
  const catalog = {
    ...data,
    groups: {
      apps: {
        name: 'Apps',
        projects: [
          { ...app, repository: { url: 'https://github.com/example/story' } },
        ],
      },
    },
  };
  const { unmount } = render(<ProjectShowcase data={catalog} />);
  await user.type(screen.getByRole('searchbox'), 'fantasy');
  await user.click(screen.getByRole('button', { name: 'Compact' }));
  expect(screen.getByRole('button', { name: 'Compact' })).toHaveAttribute(
    'aria-pressed',
    'true'
  );
  expect(screen.getByRole('link', { name: 'Story Tool' })).toHaveAttribute(
    'href',
    '/Story Tool/'
  );
  expect(
    screen.getByRole('link', { name: 'View Story Tool source on GitHub' })
  ).toHaveAttribute('href', 'https://github.com/example/story');
  expect(screen.getByRole('status')).toHaveTextContent('1 of 1 projects');
  unmount();
  render(<ProjectShowcase data={catalog} />);
  expect(screen.getByRole('button', { name: 'Compact' })).toHaveAttribute(
    'aria-pressed',
    'true'
  );
  await user.click(screen.getByRole('button', { name: 'Cards' }));
  expect(screen.getByRole('button', { name: 'Cards' })).toHaveAttribute(
    'aria-pressed',
    'true'
  );
});

it('finds projects by description without exposing implementation badges', async () => {
  const user = userEvent.setup();
  render(<ProjectShowcase data={data} />);
  await user.type(screen.getByRole('searchbox'), '  FANTASY');
  expect(screen.getByRole('link', { name: 'Story Tool' })).toBeInTheDocument();
  expect(
    screen.queryByRole('link', { name: 'Space Game' })
  ).not.toBeInTheDocument();
  expect(screen.getByText('Prototype')).toBeInTheDocument();
  expect(screen.queryByText('React')).not.toBeInTheDocument();
});

it('limits recent hatches to five and only links public URLs, never filesystem paths', () => {
  vi.mocked(useProjectUpdates).mockReturnValue({
    data: Array.from({ length: 6 }, (_, i) => ({
      name: `Hatch ${i}`,
      type: 'game_apps',
      path: `D:/games/hatch${i}`,
      lastUpdated: '2026-09-16T12:00:00Z',
      productionUrl: i === 0 ? 'https://example.com/game' : undefined,
    })),
    isLoading: false,
    error: null,
    isFetching: false,
    refetch: vi.fn(),
  } as unknown as ReturnType<typeof useProjectUpdates>);
  const { rerender } = render(
    <MemoryRouter>
      <LatestUpdates />
    </MemoryRouter>
  );
  expect(screen.getAllByRole('listitem')).toHaveLength(5);
  expect(screen.getByRole('link', { name: /Hatch 0/ })).toHaveAttribute(
    'href',
    'https://example.com/game'
  );
  expect(
    screen.queryByRole('link', { name: /Hatch 1/ })
  ).not.toBeInTheDocument();
  rerender(
    <MemoryRouter>
      <LatestUpdates showAll />
    </MemoryRouter>
  );
  expect(screen.getAllByRole('listitem')).toHaveLength(6);
});
