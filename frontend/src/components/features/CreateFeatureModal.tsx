import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { CreateFeatureRequest } from '../../types/featureRequest';
import { useFeatureRequestUser } from '../../stores/featureRequestStore';

interface CreateFeatureModalProps {
  onClose: () => void;
  onCreate: (data: CreateFeatureRequest) => Promise<void>;
  projects?: Array<{ id: number; title: string }>;
}

const FEATURE_TYPES = [
  { value: 'enhancement', label: 'Enhancement', icon: '⚡' },
  { value: 'new_feature', label: 'New Feature', icon: '✨' },
  { value: 'bug_fix', label: 'Bug Fix', icon: '🐛' },
  { value: 'ui_improvement', label: 'UI Improvement', icon: '🎨' },
  { value: 'performance', label: 'Performance', icon: '🚀' },
];


export const CreateFeatureModal = ({ onClose, onCreate, projects = [] }: CreateFeatureModalProps) => {
  const user = useFeatureRequestUser();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  
  const [formData, setFormData] = useState<CreateFeatureRequest>({
    title: '',
    description: '',
    feature_type: 'enhancement',
    project_id: undefined,
    tags: [],
  });

  const [tagInput, setTagInput] = useState('');

  const canAfford = (user?.egg_balance || 0) >= 100;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!user) {
      setError('You must be logged in to create a feature request');
      return;
    }

    if (!canAfford) {
      setError('You need at least 100 eggs to create a feature request');
      return;
    }

    if (!formData.title.trim() || !formData.description.trim()) {
      setError('Title and description are required');
      return;
    }

    if (projects.length === 0) {
      setError('No projects available. Contact an admin to create projects first.');
      return;
    }

    if (!formData.project_id) {
      setError('Please select a project for this feature request');
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      await onCreate(formData);
      onClose();
    } catch (error: unknown) {
      setError((error as Error).message || 'Failed to create feature request');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleAddTag = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ',') {
      e.preventDefault();
      const tag = tagInput.trim();
      if (tag && !(formData.tags || []).includes(tag)) {
        setFormData(prev => ({
          ...prev,
          tags: [...(prev.tags || []), tag]
        }));
        setTagInput('');
      }
    }
  };

  const removeTag = (tagToRemove: string) => {
    setFormData(prev => ({
      ...prev,
      tags: (prev.tags || []).filter(tag => tag !== tagToRemove)
    }));
  };

  const handleOverlayClick = (e: React.MouseEvent) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  if (!user) return null;

  return (
    <AnimatePresence>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        exit={{ opacity: 0 }}
        className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        onClick={handleOverlayClick}
      >
        <motion.div
          initial={{ opacity: 0, scale: 0.95, y: 20 }}
          animate={{ opacity: 1, scale: 1, y: 0 }}
          exit={{ opacity: 0, scale: 0.95, y: 20 }}
          className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
        >
          <form onSubmit={handleSubmit}>
            {/* Header */}
            <div className="px-6 py-4 border-b border-gray-200">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-semibold text-gray-900">Create Feature Request</h3>
                <button
                  type="button"
                  onClick={onClose}
                  className="text-gray-400 hover:text-gray-600 transition-colors"
                >
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              
              {/* Cost Info */}
              <div className="flex items-center justify-between mt-3 p-3 bg-blue-50 rounded-lg">
                <div className="flex items-center gap-2">
                  <span className="text-lg">🥚</span>
                  <span className="text-sm font-medium text-gray-700">Cost: 100 eggs</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-sm text-gray-600">Your balance:</span>
                  <span className={`font-semibold ${canAfford ? 'text-green-600' : 'text-red-600'}`}>
                    {user.egg_balance.toLocaleString()}
                  </span>
                </div>
              </div>

              {!canAfford && (
                <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded-md">
                  <p className="text-sm text-red-600">
                    You don't have enough eggs to create a feature request. Claim your daily reward or vote on features to earn more eggs!
                  </p>
                </div>
              )}
            </div>

            {/* Content */}
            <div className="px-6 py-4 space-y-6">
              {/* Title */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Title <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={formData.title}
                  onChange={(e) => setFormData(prev => ({ ...prev, title: e.target.value }))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Brief, descriptive title for your feature request"
                  maxLength={255}
                  required
                />
              </div>

              {/* Project Selection */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Project <span className="text-red-500">*</span>
                </label>
                {projects.length > 0 ? (
                  <select
                    value={formData.project_id || ''}
                    onChange={(e) => setFormData(prev => ({ 
                      ...prev, 
                      project_id: e.target.value ? parseInt(e.target.value) : undefined 
                    }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    required
                  >
                    <option value="">Select a project</option>
                    {projects.map((project) => (
                      <option key={project.id} value={project.id}>
                        {project.title}
                      </option>
                    ))}
                  </select>
                ) : (
                  <div className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500 text-sm">
                    No projects available. Contact an admin to create projects.
                  </div>
                )}
              </div>

              {/* Feature Type */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Feature Type
                </label>
                <select
                  value={formData.feature_type}
                  onChange={(e) => setFormData(prev => ({ 
                    ...prev, 
                    feature_type: e.target.value as 'new_feature' | 'improvement' | 'bug_fix' | 'integration' | 'other' 
                  }))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  {FEATURE_TYPES.map((type) => (
                    <option key={type.value} value={type.value}>
                      {type.icon} {type.label}
                    </option>
                  ))}
                </select>
              </div>

              {/* Description */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Description <span className="text-red-500">*</span>
                </label>
                <textarea
                  value={formData.description}
                  onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  rows={4}
                  placeholder="Detailed description of the feature you're requesting"
                  required
                />
              </div>


              {/* Tags */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Tags
                </label>
                <input
                  type="text"
                  value={tagInput}
                  onChange={(e) => setTagInput(e.target.value)}
                  onKeyDown={handleAddTag}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Add tags (press Enter or comma to add)"
                />
                
                {(formData.tags || []).length > 0 && (
                  <div className="flex flex-wrap gap-2 mt-2">
                    {(formData.tags || []).map((tag) => (
                      <span
                        key={tag}
                        className="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 text-sm rounded-md"
                      >
                        {tag}
                        <button
                          type="button"
                          onClick={() => removeTag(tag)}
                          className="text-blue-500 hover:text-blue-700"
                        >
                          ×
                        </button>
                      </span>
                    ))}
                  </div>
                )}
              </div>

              {/* Error Message */}
              {error && (
                <div className="p-3 bg-red-50 border border-red-200 rounded-md">
                  <p className="text-sm text-red-600">{error}</p>
                </div>
              )}
            </div>

            {/* Footer */}
            <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
              <button
                type="button"
                onClick={onClose}
                disabled={isSubmitting}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors disabled:opacity-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isSubmitting || !canAfford}
                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                {isSubmitting ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    Creating...
                  </>
                ) : (
                  <>
                    <span className="text-base">🥚</span>
                    Create Feature (100 eggs)
                  </>
                )}
              </button>
            </div>
          </form>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  );
};