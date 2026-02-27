import React, { useState } from 'react';
import type { Project } from '../types/projects';

interface ProjectFormProps {
  project?: Partial<Project>;
  groupOptions?: string[];
  onChange: (updates: Partial<Project>) => void;
  onSuggestDescription?: (title: string, description?: string) => Promise<string>;
  onSubmit: () => void;
  submitLabel?: string;
}

const ProjectForm: React.FC<ProjectFormProps> = ({
  project = {},
  groupOptions = ['other'],
  onChange,
  onSuggestDescription,
  onSubmit,
  submitLabel = 'Save',
}) => {
  const p = project ?? {};
  const stageOptions = ['Static', 'React', 'API', 'Auth'];
  const statusOptions = ['Planning', 'In Development', 'MVP', 'Published'];
  const normalizedStage = stageOptions.includes(p.stage ?? '')
    ? (p.stage as string)
    : 'Static';
  const normalizedStatus = statusOptions.includes(p.status ?? '')
    ? (p.status as string)
    : 'Planning';
  const normalizeGroup = (value: string): string =>
    value.trim().toLowerCase().replace(/\s+/g, '_');
  const availableGroupOptions = Array.from(
    new Set(
      [...groupOptions, p.group_name ?? '', 'other']
        .map(normalizeGroup)
        .filter(option => option.length > 0)
    )
  ).sort((a, b) => a.localeCompare(b));
  const currentGroup = normalizeGroup(p.group_name ?? '');
  const normalizedGroup = availableGroupOptions.includes(currentGroup)
    ? currentGroup
    : 'other';
  const [isSuggesting, setIsSuggesting] = useState(false);
  const [suggestError, setSuggestError] = useState<string | null>(null);

  const handleSuggest = async () => {
    if (!onSuggestDescription) return;
    if (!p.title?.trim()) {
      setSuggestError('Enter a title before generating a description.');
      return;
    }

    setIsSuggesting(true);
    setSuggestError(null);
    try {
      const suggested = await onSuggestDescription(
        p.title.trim(),
        p.description ?? ''
      );
      onChange({ ...p, description: suggested });
    } catch (error) {
      setSuggestError(
        error instanceof Error
          ? error.message
          : 'Failed to generate description.'
      );
    } finally {
      setIsSuggesting(false);
    }
  };

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
        <select
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={normalizedGroup}
          onChange={e => onChange({ ...p, group_name: e.target.value })}
        >
          {availableGroupOptions.map(option => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      </div>

      <div className="col-span-1 md:col-span-2">
        <div className="flex items-center justify-between gap-3">
          <label className="block text-sm font-medium text-gray-700">
            Description
          </label>
          {onSuggestDescription && (
            <button
              type="button"
              onClick={() => void handleSuggest()}
              disabled={isSuggesting}
              className="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100 disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {isSuggesting ? 'Generating...' : 'AI Suggest (14 words)'}
            </button>
          )}
        </div>
        <textarea
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={p.description ?? ''}
          onChange={e => onChange({ ...p, description: e.target.value })}
        />
        {suggestError && (
          <p className="mt-1 text-sm text-red-600">{suggestError}</p>
        )}
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">Stage</label>
        <select
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={normalizedStage}
          onChange={e => onChange({ ...p, stage: e.target.value })}
        >
          {stageOptions.map(option => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700">
          Status
        </label>
        <select
          className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
          value={normalizedStatus}
          onChange={e => onChange({ ...p, status: e.target.value })}
        >
          {statusOptions.map(option => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
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
