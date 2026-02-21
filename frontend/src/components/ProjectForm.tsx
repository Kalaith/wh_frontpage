import React from 'react';
import type { Project } from '../types/projects';

interface ProjectFormProps {
  project?: Partial<Project>;
  onChange: (updates: Partial<Project>) => void;
  onSubmit: () => void;
  submitLabel?: string;
}

const ProjectForm: React.FC<ProjectFormProps> = ({
  project = {},
  onChange,
  onSubmit,
  submitLabel = 'Save',
}) => {
  const p = project ?? {};

  return (
    <form
      onSubmit={e => {
        e.preventDefault();
        onSubmit();
      }}
      className="grid grid-cols-1 md:grid-cols-2 gap-4"
    >
      <div className="col-span-1 md:col-span-2">
        <label className="block text-sm font-medium text-gray-700">Title</label>
        <input
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.title ?? ''}
          onChange={e => onChange({ ...p, title: e.target.value })}
          required
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">Path</label>
        <input
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.path ?? ''}
          onChange={e => onChange({ ...p, path: e.target.value })}
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">
          Group Name
        </label>
        <input
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.group_name ?? ''}
          onChange={e => onChange({ ...p, group_name: e.target.value })}
        />
      </div>

      <div className="col-span-1 md:col-span-2">
        <label className="block text-sm font-medium text-gray-700">
          Description
        </label>
        <textarea
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.description ?? ''}
          onChange={e => onChange({ ...p, description: e.target.value })}
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">Stage</label>
        <input
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.stage ?? ''}
          onChange={e => onChange({ ...p, stage: e.target.value })}
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">
          Status
        </label>
        <input
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.status ?? ''}
          onChange={e => onChange({ ...p, status: e.target.value })}
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">
          Version
        </label>
        <input
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.version ?? ''}
          onChange={e => onChange({ ...p, version: e.target.value })}
        />
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">
          Repository URL
        </label>
        <input
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.repository?.url ?? ''}
          onChange={e =>
            onChange({
              ...p,
              repository: { ...(p.repository ?? {}), url: e.target.value },
            })
          }
        />
      </div>

      <div className="col-span-1 md:col-span-2 space-y-4">
        {/* Toggle Controls */}
        <div className="flex items-center gap-6">
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={p.show_on_homepage !== false}
              onChange={e =>
                onChange({ ...p, show_on_homepage: e.target.checked })
              }
              className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <span className="text-sm font-medium text-gray-700">
              Show on Homepage
            </span>
          </label>
        </div>

        <button
          type="submit"
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
        >
          {submitLabel}
        </button>
      </div>
    </form>
  );
};

export default ProjectForm;
