import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import SuggestionCard from '../components/tracker/SuggestionCard';
import ProjectSuggestionForm from '../components/tracker/ProjectSuggestionForm';
import { useProjectSuggestions, useCreateProjectSuggestion } from '../hooks/useTrackerQuery';

const ProjectSuggestionsPage: React.FC = () => {
  const [showForm, setShowForm] = useState(false);
  const [filters, setFilters] = useState({
    group: '',
    sort_by: 'votes'
  });

  const { data: suggestions, isLoading, error } = useProjectSuggestions({
    ...filters,
    sort_direction: 'desc'
  });

  const createSuggestionMutation = useCreateProjectSuggestion();

  const handleSubmitSuggestion = async (data: any) => {
    try {
      await createSuggestionMutation.mutateAsync(data);
      setShowForm(false);
    } catch (error) {
      console.error('Failed to submit project suggestion:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="max-w-6xl mx-auto p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div className="text-center py-8">
          <p className="text-lg text-gray-600">Loading project suggestions...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-6xl mx-auto p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div className="text-center py-8">
          <p className="text-lg text-red-600">Error loading project suggestions: {error.message}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
      {/* Navigation */}
      <nav className="mb-6 text-sm text-blue-600">
        <Link to="/tracker" className="hover:text-blue-800">← Back to Tracker</Link>
      </nav>

      {/* Header */}
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-2">
            Project Suggestions
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
            Community-driven project ideas for the WebHatchery ecosystem
          </p>
        </div>
        <button
          onClick={() => setShowForm(true)}
          className="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
        >
          Suggest Project
        </button>
      </div>

      {/* Suggestion Form Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
          <div className="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                  Suggest New Project
                </h2>
                <button
                  onClick={() => setShowForm(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  ×
                </button>
              </div>
              <ProjectSuggestionForm
                onSubmit={handleSubmitSuggestion}
                onCancel={() => setShowForm(false)}
              />
            </div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="mb-6 flex flex-wrap gap-2">
        <select 
          value={filters.group}
          onChange={(e) => setFilters(prev => ({ ...prev, group: e.target.value }))}
          className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Groups</option>
          <option value="Fiction Projects">Fiction Projects</option>
          <option value="Web Applications">Web Applications</option>
          <option value="Games & Game Design">Games & Game Design</option>
          <option value="Game Design">Game Design</option>
          <option value="AI & Development Tools">AI & Development Tools</option>
        </select>
        <select 
          value={filters.sort_by}
          onChange={(e) => setFilters(prev => ({ ...prev, sort_by: e.target.value }))}
          className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="votes">Sort by Votes</option>
          <option value="date">Sort by Date</option>
          <option value="name">Sort by Name</option>
        </select>
      </div>

      {/* Suggestions Grid */}
      <div className="grid gap-6 md:grid-cols-2">
        {suggestions && suggestions.length > 0 ? (
          suggestions.map((suggestion) => (
            <SuggestionCard
              key={suggestion.id}
              title={suggestion.name}
              description={suggestion.description}
              group={suggestion.suggested_group}
              rationale={suggestion.rationale}
              votes={suggestion.votes}
              date={suggestion.created_at}
            />
          ))
        ) : (
          <div className="col-span-2 text-center py-8 text-gray-500">
            <p>No project suggestions found.</p>
            <button
              onClick={() => setShowForm(true)}
              className="text-green-600 hover:text-green-800 underline mt-2"
            >
              Suggest the first project!
            </button>
          </div>
        )}
      </div>

      {/* Load More */}
      <div className="mt-8 text-center">
        <button className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
          Load More Suggestions
        </button>
      </div>
    </div>
  );
};

export default ProjectSuggestionsPage;