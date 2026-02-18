import React, { useState } from 'react';
import SuggestionCard from '../components/tracker/SuggestionCard';
import ProjectSuggestionForm from '../components/tracker/ProjectSuggestionForm';
import { useProjectSuggestions, useCreateProjectSuggestion } from '../hooks/useTrackerQuery';
import { IdeaDetailModal } from '../components/ideas/IdeaDetailModal';
import { ProjectSuggestion } from '../api/trackerApi';

const IdeasPage: React.FC = () => {
    const [showForm, setShowForm] = useState(false);
    const [selectedIdea, setSelectedIdea] = useState<ProjectSuggestion | null>(null);
    const [filters, setFilters] = useState({
        group: '',
        sort_by: 'votes'
    });

    const { data: suggestions, isLoading, error } = useProjectSuggestions({
        ...filters,
        sort_direction: 'desc'
    });

    const createSuggestionMutation = useCreateProjectSuggestion();

    const handleSubmitSuggestion = async (data: { name: string; description: string; group: string; rationale: string }) => {
        try {
            await createSuggestionMutation.mutateAsync(data);
            setShowForm(false);
        } catch (error) {
            console.error('Failed to submit idea:', error);
        }
    };

    if (isLoading) {
        return (
            <div className="max-w-6xl mx-auto p-6">
                <div className="text-center py-8">
                    <p className="text-lg text-gray-600">Loading ideas...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="max-w-6xl mx-auto p-6">
                <div className="text-center py-8">
                    <p className="text-lg text-red-600">Error loading ideas: {error.message}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="max-w-6xl mx-auto p-6">
            {/* Header */}
            <div className="flex items-center justify-between mb-8">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 mb-2">
                        Ideas
                    </h1>
                    <p className="text-gray-600">
                        A place for new project ideas that haven't been hatched yet.
                    </p>
                </div>
                <button
                    onClick={() => setShowForm(true)}
                    className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors shadow-sm"
                >
                    Submit New Idea
                </button>
            </div>

            {/* Filters */}
            <div className="mb-6 flex flex-wrap gap-2">
                <select
                    value={filters.group}
                    onChange={(e) => setFilters(prev => ({ ...prev, group: e.target.value }))}
                    className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Categories</option>
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
                    <option value="votes">Top Voted</option>
                    <option value="date">Newest First</option>
                    <option value="name">Alphabetical</option>
                </select>
            </div>

            {/* Ideas Grid */}
            <div className="grid gap-6 md:grid-cols-2">
                {suggestions && suggestions.length > 0 ? (
                    suggestions.map((suggestion) => (
                        <div key={suggestion.id} onClick={() => setSelectedIdea(suggestion)} className="cursor-pointer transition-transform hover:scale-[1.01]">
                            <SuggestionCard
                                title={suggestion.name}
                                description={suggestion.description}
                                group={suggestion.suggested_group}
                                rationale={suggestion.rationale}
                                votes={suggestion.votes}
                                date={suggestion.created_at}
                            />
                        </div>
                    ))
                ) : (
                    <div className="col-span-2 text-center py-8 text-gray-500">
                        <p>No ideas found.</p>
                        <button
                            onClick={() => setShowForm(true)}
                            className="text-blue-600 hover:text-blue-800 underline mt-2"
                        >
                            Be the first to suggest an idea!
                        </button>
                    </div>
                )}
            </div>

            {/* Submission Form Modal */}
            {showForm && (
                <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-xl font-semibold text-gray-900">
                                    Submit New Idea
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

            {/* Detail Modal */}
            {selectedIdea && (
                <IdeaDetailModal
                    idea={selectedIdea}
                    onClose={() => setSelectedIdea(null)}
                />
            )}
        </div>
    );
};

export default IdeasPage;
