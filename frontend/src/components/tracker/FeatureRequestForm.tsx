import React, { useState } from 'react';
import { useProjects } from '../../hooks/useProjectsQuery';
import { getAllProjects } from '../../utils/projectUtils';
import type { FeatureRequestData } from '../../types/featureRequest';

interface FeatureRequestFormProps {
  onSubmit?: (data: FeatureRequestData) => void;
  onCancel?: () => void;
}

const FeatureRequestForm: React.FC<FeatureRequestFormProps> = ({
  onSubmit,
  onCancel,
}) => {
  const { data: projectsData } = useProjects();
  const [formData, setFormData] = useState<FeatureRequestData>({
    title: '',
    description: '',
    category: 'Bug Fix',
    priority: 'Low',
    tags: '',
    project_id: undefined,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit?.(formData);
  };

  const handleChange = (
    field: keyof FeatureRequestData,
    value: string | number
  ) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleProjectChange = (value: string) => {
    const projectId = value ? parseInt(value) : undefined;
    setFormData(prev => ({ ...prev, project_id: projectId }));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4 mt-8">
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Title *
        </label>
        <input
          type="text"
          value={formData.title}
          onChange={e => handleChange('title', e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          required
        />
      </div>
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Description *
        </label>
        <textarea
          value={formData.description}
          onChange={e => handleChange('description', e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          rows={4}
          required
        />
      </div>
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Project
        </label>
        <select
          value={formData.project_id?.toString() ?? ''}
          onChange={e => handleProjectChange(e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Select a project (optional)</option>
          {getAllProjects(projectsData).map(project => (
            <option key={project.id} value={project.id}>
              {project.title} {project.group_name && `(${project.group_name})`}
            </option>
          ))}
        </select>
      </div>
      <div className="flex gap-4">
        <div className="flex-1 space-y-2">
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Category
          </label>
          <select
            value={formData.category}
            onChange={e => handleChange('category', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option>Bug Fix</option>
            <option>New Feature</option>
            <option>Enhancement</option>
            <option>UI/UX Improvement</option>
          </select>
        </div>
        <div className="flex-1 space-y-2">
          <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Priority
          </label>
          <select
            value={formData.priority}
            onChange={e => handleChange('priority', e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option>Low</option>
            <option>Medium</option>
            <option>High</option>
            <option>Critical</option>
          </select>
        </div>
      </div>
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Tags (comma-separated)
        </label>
        <input
          type="text"
          value={formData.tags}
          onChange={e => handleChange('tags', e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="ui, performance, mobile"
        />
      </div>
      <div className="flex gap-4 mt-4">
        <button
          type="button"
          onClick={onCancel}
          className="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
        >
          Cancel
        </button>
        <button
          type="submit"
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          Submit Request
        </button>
      </div>
    </form>
  );
};

export default FeatureRequestForm;
