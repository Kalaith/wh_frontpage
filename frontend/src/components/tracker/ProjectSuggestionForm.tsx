import React, { useState } from 'react';
import type { ProjectSuggestionData } from '../../types/tracker';

interface ProjectSuggestionFormProps {
  onSubmit?: (data: ProjectSuggestionData) => void;
  onCancel?: () => void;
}

const ProjectSuggestionForm: React.FC<ProjectSuggestionFormProps> = ({
  onSubmit,
  onCancel
}) => {
  const [formData, setFormData] = useState<ProjectSuggestionData>({
    name: '',
    description: '',
    group: 'Fiction Projects',
    rationale: ''
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit?.(formData);
  };

  const handleChange = (field: keyof ProjectSuggestionData, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4 mt-8">
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Project Name *</label>
        <input 
          type="text" 
          value={formData.name}
          onChange={(e) => handleChange('name', e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
          required 
        />
      </div>
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Description *</label>
        <textarea 
          value={formData.description}
          onChange={(e) => handleChange('description', e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
          rows={4} 
          required
        />
      </div>
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Suggested Group</label>
        <select 
          value={formData.group}
          onChange={(e) => handleChange('group', e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option>Fiction Projects</option>
          <option>Web Applications</option>
          <option>Games & Game Design</option>
          <option>Game Design</option>
          <option>AI & Development Tools</option>
        </select>
      </div>
      <div className="space-y-2">
        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Rationale *</label>
        <textarea 
          value={formData.rationale}
          onChange={(e) => handleChange('rationale', e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
          rows={3} 
          required
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
          Submit Suggestion
        </button>
      </div>
    </form>
  );
};

export default ProjectSuggestionForm;