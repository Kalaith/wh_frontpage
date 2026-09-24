import {
  cleanup,
  render,
  screen,
  waitFor,
  within,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import HomePage from '../pages/HomePage';
import type { HealthSummary, ProjectHealth } from '../types/projectHealth';

vi.mock('../hooks/useProjectsQuery', () => ({
  useHomepageProjects: () => ({ isLoading: false }),
}));
vi.mock('./ProjectShowcase', () => ({ ProjectShowcase: () => null }));
vi.mock('./ArchiveSection', () => ({ ArchiveSection: () => null }));
vi.mock('./LatestUpdates', () => ({ LatestUpdates: () => null }));
vi.mock('./Footer', () => ({ Footer: () => null }));

const summary: HealthSummary = {
  overall_status: 'critical',
  total_projects: 12,
  healthy_projects: 8,
  warning_projects: 3,
  critical_projects: 1,
  top_issues: [],
  urgent_recommendations: [],
};
const criticalProject: ProjectHealth = {
  project_name: 'Space Game',
  status: 'critical',
  score: 25,
  issues: [
    {
      severity: 'critical',
      code: 'deployment',
      message: 'Missing deployment',
      project: 'Space Game',
    },
  ],
  checks: [],
  last_checked: '2026-09-16T00:00:00Z',
};

let queryClient: QueryClient;
let summaryResponse: unknown;
let checkResponse: unknown;
let fetchMock: ReturnType<typeof vi.fn>;

beforeEach(() => {
  queryClient = new QueryClient({
    defaultOptions: { queries: { retry: false }, mutations: { retry: false } },
  });
  summaryResponse = { success: true, data: summary };
  checkResponse = { success: true, data: {} };
  fetchMock = vi.fn(async (url: string) => {
    let body: unknown;
    if (url.endsWith('/health/summary')) body = summaryResponse;
    else if (url.endsWith('/health/critical'))
      body = { success: true, data: [criticalProject] };
    else if (url.endsWith('/health/check')) body = checkResponse;
    else throw new Error(`Unexpected request: ${url}`);
    return new Response(JSON.stringify(body), {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    });
  });
  vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
  cleanup();
  queryClient.clear();
  vi.unstubAllGlobals();
});

async function openHealth() {
  const user = userEvent.setup();
  render(
    <QueryClientProvider client={queryClient}>
      <HomePage />
    </QueryClientProvider>
  );
  expect(fetchMock).not.toHaveBeenCalled();
  await user.click(screen.getByText('Behind the scenes'));
  return user;
}

it('loads summary counts and critical projects from the backend response when expanded', async () => {
  const user = await openHealth();
  await screen.findByText('Total');
  for (const [label, value] of [
    ['Total', '12'],
    ['Healthy', '8'],
    ['Warning', '3'],
    ['Critical', '1'],
  ]) {
    expect(
      within(screen.getByText(label).parentElement!).getByText(value)
    ).toBeInTheDocument();
  }
  expect(await screen.findByText('Space Game')).toBeInTheDocument();
  expect(screen.getByText('Score: 25/100')).toBeInTheDocument();
  await user.click(screen.getByText('Behind the scenes'));
  await waitFor(() =>
    expect(screen.queryByText('Total')).not.toBeInTheDocument()
  );
  await user.click(screen.getByText('Behind the scenes'));
  expect(await screen.findByText('Total')).toBeInTheDocument();
});

it.each([
  { success: false, message: 'Health service unavailable' },
  { success: true },
  { success: true, data: null },
])(
  'shows an error and allows recovery for an unusable response: %j',
  async response => {
    summaryResponse = response;
    const user = await openHealth();
    expect(await screen.findByRole('alert')).toHaveTextContent(
      'Unable to load health data'
    );
    summaryResponse = { success: true, data: summary };
    await user.click(screen.getByRole('button', { name: 'Try again' }));
    expect(await screen.findByText('Total')).toBeInTheDocument();
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  }
);

it('reports a failed refresh and reloads counts after a successful retry', async () => {
  const user = await openHealth();
  await screen.findByText('Total');
  checkResponse = { success: false, message: 'Health check failed' };
  await user.click(screen.getByRole('button', { name: 'Refresh' }));
  expect(await screen.findByRole('alert')).toHaveTextContent(
    'Unable to refresh health data'
  );
  expect(screen.getByText('Total')).toBeInTheDocument();
  checkResponse = { success: true, data: {} };
  summaryResponse = { success: true, data: { ...summary, total_projects: 13 } };
  await user.click(screen.getByRole('button', { name: 'Refresh' }));
  expect(await screen.findByText('13')).toBeInTheDocument();
  expect(screen.queryByRole('alert')).not.toBeInTheDocument();
  expect(fetchMock).toHaveBeenCalledWith(
    expect.stringContaining('/health/check'),
    expect.objectContaining({ method: 'POST' })
  );
});
