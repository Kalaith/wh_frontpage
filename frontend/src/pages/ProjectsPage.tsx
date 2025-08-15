import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import type { Project } from '../types/projects';
import api from '../api/api';
import { ProjectForm } from '../components/ProjectForm';

const ProjectsPage: React.FC = () => {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [createData, setCreateData] = useState<Partial<Project>>({ title: '', group_name: 'other' });
  const [editingId, setEditingId] = useState<null | number>(null);
  const [editingData, setEditingData] = useState<Partial<Project> | null>(null);

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      try {
        const res = await api.getProjects();
        if (res.success && res.data) {
          // flatten groups to list
          const flat: Project[] = [];
          Object.values(res.data.groups || {}).forEach((grp: any) => {
            grp.projects.forEach((p: any) => flat.push(p));
          });
          setProjects(flat);
        } else {
          setError(res.error?.message || 'Failed to load projects');
        }
      } catch (e) {
        setError((e as Error).message);
      } finally {
        setLoading(false);
      }
    };

    load();
  }, []);

  const handleCreate = async (data: Partial<Project>) => {
    try {
      const createRes = await api.createProject(data);
      if (createRes.success && createRes.data) {
        setProjects(prev => [createRes.data as Project, ...prev]);
      } else {
        setError(createRes.error?.message || 'Failed to create project');
      }
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const handleEdit = (project: Project) => {
    setEditingId(project.id ?? null);
    setEditingData(project);
  };

  const handleUpdate = async (updates: Partial<Project>) => {
    if (!editingId) {
      setError('No project selected for update');
      return;
    }

    try {
      const updateRes = await api.updateProject(editingId, updates);
      if (updateRes.success && updateRes.data) {
        setProjects(prev => prev.map(p => (p.id === editingId ? (updateRes.data as Project) : p)));
        setEditingId(null);
        setEditingData(null);
      } else {
        setError(updateRes.error?.message || 'Failed to update project');
      }
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const handleDelete = async (projectId?: number, index?: number) => {
    if (projectId) {
      try {
        const del = await api.deleteProject(projectId);
        if (del.success) {
          setProjects(prev => prev.filter(p => p.id !== projectId));
        } else {
          setError(del.error?.message || 'Failed to delete project');
        }
      } catch (err) {
        setError((err as Error).message);
      }
    } else if (typeof index === 'number') {
      setProjects(prev => prev.filter((_, i) => i !== index));
    }
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
          onChange={(d) => setCreateData(prev => ({ ...(prev || {}), ...(d || {}) }))}
          onSubmit={() => handleCreate(createData)}
          submitLabel="Create"
        />
      </section>

      <section className="mb-6">
        <h3 className="text-lg font-medium mb-3">Existing Projects ({projects.length})</h3>

        {/* Group projects by group_name and sort groups alphabetically */}
        {(() => {
          const groups: Record<string, Project[]> = {};
          projects.forEach(p => {
            const g = p.group_name || 'other';
            if (!groups[g]) groups[g] = [];
            groups[g].push(p);
          });

          const groupNames = Object.keys(groups).sort((a, b) => a.localeCompare(b));

          return (
            <div>
              {groupNames.map(groupName => (
                <div key={groupName} className="mb-4">
                  <h4 className="text-md font-semibold mb-2">{groupName} ({groups[groupName].length})</h4>
                  <ul>
                    {groups[groupName]
                      .slice()
                      .sort((a, b) => (a.title || '').localeCompare(b.title || ''))
                      .map((p, idx) => (
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
                              <button className="px-3 py-1 bg-red-600 text-white rounded" onClick={() => handleDelete(p.id, idx)}>Delete</button>
                            </div>
                          </div>

              {editingId === p.id && (
                            <div className="border-t px-3 pb-3">
                              <ProjectForm
                project={(editingData as Project) ?? p}
                onChange={(d) => setEditingData(prev => ({ ...(prev || {}), ...(d || {}) }))}
                onSubmit={() => handleUpdate((editingData as Partial<Project>) || {})}
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
            </div>
          );
        })()}
      </section>

  {/* Only inline edit under the selected item - remove the global edit panel */}
    </div>
  );
};

export default ProjectsPage;
