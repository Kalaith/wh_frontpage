import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import type { Project } from '../types/projects';
import ProjectForm from '../components/ProjectForm';
import useProjects from '../hooks/useProjects';

const ProjectsPage: React.FC = () => {
  const { grouped, loading, error, createProject, updateProject, deleteProject, projects } = useProjects();
  const [createData, setCreateData] = useState<Partial<Project>>({ title: '', group_name: 'other' });
  const [editingId, setEditingId] = useState<null | number>(null);
  const [editingData, setEditingData] = useState<Partial<Project> | null>(null);

  const handleCreate = async (data: Partial<Project>) => {
    await createProject(data);
    setCreateData({ title: '', group_name: 'other' });
  };

  const handleEdit = (project: Project) => {
    setEditingId(project.id ?? null);
    setEditingData(project);
  };

  const handleUpdate = async (updates: Partial<Project>) => {
    if (!editingId) return;
    await updateProject(editingId, updates);
    setEditingId(null);
    setEditingData(null);
  };

  const handleDelete = async (projectId?: number) => {
    if (!projectId) return;
    await deleteProject(projectId);
  };

  if (loading) return <div className="max-w-4xl mx-auto p-4"><p>Loading projects...</p></div>;
  if (error) return <div className="max-w-4xl mx-auto p-4"><p className="text-red-600">Error: {error}</p></div>;

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
          onChange={(d: Partial<Project>) => setCreateData(prev => ({ ...(prev || {}), ...(d || {}) }))}
          onSubmit={() => void handleCreate(createData)}
          submitLabel="Create"
        />
      </section>

      <section className="mb-6">
        <h3 className="text-lg font-medium mb-3">Existing Projects ({projects.length})</h3>

  {Object.keys(grouped).sort((a, b) => a.localeCompare(b)).map((groupName: string) => (
          <div key={groupName} className="mb-4">
            <h4 className="text-md font-semibold mb-2">{groupName} ({grouped[groupName].length})</h4>
            <ul>
              {grouped[groupName].map((p: Project, idx: number) => (
                <li key={p.id ?? `${groupName}-${idx}`} className="bg-white rounded mb-3 shadow-sm">
                  <div className="flex justify-between items-start p-3">
                    <div className="flex flex-col">
                      <div className="flex items-center gap-3">
                        <strong className="text-gray-900">{p.title}</strong>
                        <span className="text-sm text-gray-500">{p.group_name}</span>
                      </div>
                      {p.description && <p className="text-sm text-gray-600 mt-1">{p.description}</p>}
                    </div>
                    <div className="flex items-center gap-2">
                      <button className="px-3 py-1 bg-amber-500 text-white rounded" onClick={() => handleEdit(p)}>Edit</button>
                      <button className="px-3 py-1 bg-red-600 text-white rounded" onClick={() => void handleDelete(p.id)}>Delete</button>
                    </div>
                  </div>

                  {editingId === p.id && (
                    <div className="border-t px-3 pb-3">
                      <ProjectForm
                        project={(editingData as Project) ?? p}
                        onChange={(d: Partial<Project>) => setEditingData(prev => ({ ...(prev || {}), ...(d || {}) }))}
                        onSubmit={() => void handleUpdate((editingData as Partial<Project>) || {})}
                        submitLabel="Update"
                      />
                      <div className="mt-2 flex gap-2">
                        <button className="px-3 py-1 border rounded" onClick={() => { setEditingId(null); setEditingData(null); }}>Cancel</button>
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
