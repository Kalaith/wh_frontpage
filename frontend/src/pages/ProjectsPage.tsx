import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import type { Project } from '../types/projects';
import ProjectForm from '../components/ProjectForm';
import {
  useProjects,
  useCreateProject,
  useUpdateProject,
  useDeleteProject,
  useSuggestProjectDescription,
} from '../hooks/useProjectsQuery';
import { useFeatureRequests } from '../hooks/useTrackerQuery';
import { getProjectCount, getGroupedProjects } from '../utils/projectUtils';
import { useAuth } from '../stores/authStore';

const ProjectsPage: React.FC = () => {
  const validStages = ['Static', 'React', 'API', 'Auth'];
  const validStatuses = ['Planning', 'In Development', 'MVP', 'Published'];
  const normalizeProjectFields = (data: Partial<Project>): Partial<Project> => {
    const next = { ...data };
    next.stage = validStages.includes((next.stage ?? '').toString())
      ? next.stage
      : 'Static';
    next.status = validStatuses.includes((next.status ?? '').toString())
      ? next.status
      : 'Planning';
    return next;
  };

  const { isAuthenticated, isAdmin, isLoading, loginWithRedirect } = useAuth();
  const { data: projectsData, isLoading: loading, error } = useProjects();
  const { data: allFeatureRequests } = useFeatureRequests({});
  const createProjectMutation = useCreateProject();
  const updateProjectMutation = useUpdateProject();
  const deleteProjectMutation = useDeleteProject();
  const suggestDescriptionMutation = useSuggestProjectDescription();

  const projectCount = getProjectCount(projectsData);
  const grouped = getGroupedProjects(projectsData);

  // Create a map of project ID to feature request counts
  const featureRequestCounts = React.useMemo(() => {
    if (!allFeatureRequests || !Array.isArray(allFeatureRequests)) return {};

    const counts: Record<
      number,
      { total: number; open: number; completed: number }
    > = {};

    allFeatureRequests.forEach(request => {
      const projectId = request.project?.id;
      if (projectId) {
        if (!counts[projectId]) {
          counts[projectId] = { total: 0, open: 0, completed: 0 };
        }
        counts[projectId].total++;

        if (request.status === 'Open' || request.status === 'open') {
          counts[projectId].open++;
        } else if (
          request.status === 'Completed' ||
          request.status === 'completed'
        ) {
          counts[projectId].completed++;
        }
      }
    });

    return counts;
  }, [allFeatureRequests]);
  const [createData, setCreateData] = useState<Partial<Project>>({
    title: '',
    group_name: 'other',
    stage: 'Static',
    status: 'Planning',
  });
  const [editingId, setEditingId] = useState<null | number>(null);
  const [editingData, setEditingData] = useState<Partial<Project> | null>(null);

  const handleCreate = async (data: Partial<Project>) => {
    await createProjectMutation.mutateAsync(normalizeProjectFields(data));
    setCreateData({
      title: '',
      group_name: 'other',
      stage: 'Static',
      status: 'Planning',
    });
  };

  const handleEdit = (project: Project) => {
    setEditingId(project.id ?? null);
    setEditingData(project);
  };

  const handleUpdate = async (updates: Partial<Project>) => {
    if (!editingId) return;
    await updateProjectMutation.mutateAsync({
      id: editingId,
      data: normalizeProjectFields(updates),
    });
    setEditingId(null);
    setEditingData(null);
  };

  const handleDelete = async (projectId?: number) => {
    if (!projectId) return;
    await deleteProjectMutation.mutateAsync(projectId);
  };

  const handleSuggestDescription = async (
    title: string,
    description?: string
  ): Promise<string> => {
    return suggestDescriptionMutation.mutateAsync({ title, description });
  };

  // Authentication checks - this page requires admin access
  if (isLoading) {
    return (
      <div className="max-w-4xl mx-auto p-4">
        <p>Loading authentication...</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="max-w-4xl mx-auto p-6">
        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
          <h2 className="text-xl font-semibold text-red-800 mb-4">
            Authentication Required
          </h2>
          <p className="text-red-700 mb-4">
            You must be logged in to access project management.
          </p>
          <button
            onClick={() => loginWithRedirect()}
            className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
          >
            Log In
          </button>
        </div>
      </div>
    );
  }

  if (!isAdmin) {
    return (
      <div className="max-w-4xl mx-auto p-6">
        <div className="bg-red-50 border border-red-200 rounded-lg p-6">
          <h2 className="text-xl font-semibold text-red-800 mb-4">
            Admin Access Required
          </h2>
          <p className="text-red-700 mb-4">
            You must be an administrator to manage projects.
          </p>
          <Link
            to="/"
            className="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded inline-block"
          >
            Return to Home
          </Link>
        </div>
      </div>
    );
  }

  if (loading)
    return (
      <div className="max-w-4xl mx-auto p-4">
        <p>Loading projects...</p>
      </div>
    );
  if (error)
    return (
      <div className="max-w-4xl mx-auto p-4">
        <p className="text-red-600">Error: {error.message}</p>
      </div>
    );

  return (
    <div className="max-w-4xl mx-auto p-6">
      <nav className="mb-4 text-sm text-sky-600">
        <Link to="/">← Back to Home</Link>
      </nav>
      <h2 className="text-2xl font-semibold mb-4">Manage Projects</h2>

      <section className="mb-6 bg-white shadow-sm rounded p-4">
        <h3 className="text-lg font-medium mb-2">Create Project</h3>
        <ProjectForm
          project={createData}
          onChange={(d: Partial<Project>) =>
            setCreateData(prev => ({ ...(prev || {}), ...(d || {}) }))
          }
          onSuggestDescription={handleSuggestDescription}
          onSubmit={() => void handleCreate(createData)}
          submitLabel="Create"
        />
      </section>

      <section className="mb-6">
        <h3 className="text-lg font-medium mb-3">
          Existing Projects ({projectCount})
        </h3>

        {Object.keys(grouped)
          .sort((a, b) => a.localeCompare(b))
          .map((groupName: string) => (
            <div key={groupName} className="mb-4">
              <h4 className="text-md font-semibold mb-2">
                {groupName} ({grouped[groupName].length})
              </h4>
              <ul>
                {grouped[groupName].map((p: Project, idx: number) => (
                  <li
                    key={p.id ?? `${groupName}-${idx}`}
                    className="bg-white rounded mb-3 shadow-sm"
                  >
                    <div className="flex justify-between items-start p-3">
                      <div className="flex flex-col flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <strong className="text-gray-900">{p.title}</strong>
                          <span className="text-sm text-gray-500">
                            {p.group_name}
                          </span>
                          {!p.show_on_homepage && (
                            <span className="px-2 py-0.5 text-xs bg-orange-100 text-orange-800 rounded border border-orange-200">
                              Not on Homepage
                            </span>
                          )}
                        </div>
                        {p.description && (
                          <p className="text-sm text-gray-600 mb-2">
                            {p.description}
                          </p>
                        )}
                        {/* Feature Request Summary */}
                        {p.id && featureRequestCounts[p.id] && (
                          <div className="flex items-center gap-4 text-sm">
                            <div className="flex items-center gap-2">
                              <span className="text-gray-500">Features:</span>
                              <span className="font-medium">
                                {featureRequestCounts[p.id].total} total
                              </span>
                              {featureRequestCounts[p.id].open > 0 && (
                                <span className="px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">
                                  {featureRequestCounts[p.id].open} open
                                </span>
                              )}
                              {featureRequestCounts[p.id].completed > 0 && (
                                <span className="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">
                                  {featureRequestCounts[p.id].completed}{' '}
                                  completed
                                </span>
                              )}
                            </div>
                            <Link
                              to={`/feature-requests?project=${p.id}`}
                              className="text-blue-600 hover:text-blue-800 underline text-sm"
                            >
                              View Features →
                            </Link>
                          </div>
                        )}
                        {p.id && !featureRequestCounts[p.id] && (
                          <div className="text-sm text-gray-400">
                            No feature requests yet
                          </div>
                        )}
                      </div>
                      <div className="flex items-center gap-2">
                        <button
                          className="px-3 py-1 bg-amber-500 text-white rounded"
                          onClick={() => handleEdit(p)}
                        >
                          Edit
                        </button>
                        <button
                          className="px-3 py-1 bg-red-600 text-white rounded"
                          onClick={() => void handleDelete(p.id)}
                        >
                          Delete
                        </button>
                      </div>
                    </div>

                    {editingId === p.id && (
                      <div className="border-t px-3 pb-3">
                        <ProjectForm
                          project={(editingData as Project) ?? p}
                          onChange={(d: Partial<Project>) =>
                            setEditingData(prev => ({
                              ...(prev ?? {}),
                              ...(d || {}),
                            }))
                          }
                          onSuggestDescription={handleSuggestDescription}
                          onSubmit={() =>
                            void handleUpdate(
                              (editingData as Partial<Project>) || {}
                            )
                          }
                          submitLabel="Update"
                        />
                        <div className="mt-2 flex gap-2">
                          <button
                            className="px-3 py-1 border rounded"
                            onClick={() => {
                              setEditingId(null);
                              setEditingData(null);
                            }}
                          >
                            Cancel
                          </button>
                        </div>
                      </div>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          ))}
      </section>
    </div>
  );
};

export default ProjectsPage;
