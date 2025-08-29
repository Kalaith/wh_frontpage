import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import RequestCard from '../components/tracker/RequestCard';
import FeatureRequestForm from '../components/tracker/FeatureRequestForm';
import { useFeatureRequests, useCreateFeatureRequest } from '../hooks/useTrackerQuery';
import { useProjects } from '../hooks/useProjectsQuery';
import { getAllProjects } from '../utils/projectUtils';

const FeatureRequestsPage: React.FC = () => {
  const [showForm, setShowForm] = useState(false);
  const [searchParams, setSearchParams] = useSearchParams();
  const [filters, setFilters] = useState({
    status: '',
    priority: '',
    category: '',
    project_id: '',
    sort_by: 'votes'
  });

  // Initialize filters from URL parameters
  useEffect(() => {
    const projectFromUrl = searchParams.get('project');
    const statusFromUrl = searchParams.get('status');
    const priorityFromUrl = searchParams.get('priority');
    const categoryFromUrl = searchParams.get('category');
    
    setFilters(prev => ({
      ...prev,
      project_id: projectFromUrl || '',
      status: statusFromUrl || '',
      priority: priorityFromUrl || '',
      category: categoryFromUrl || ''
    }));
  }, [searchParams]);

  // Helper function to update filters and URL
  const updateFilter = (key: string, value: string) => {
    const newFilters = { ...filters, [key]: value };
    setFilters(newFilters);
    
    // Update URL parameters
    const newSearchParams = new URLSearchParams(searchParams);
    if (value) {
      newSearchParams.set(key === 'project_id' ? 'project' : key, value);
    } else {
      newSearchParams.delete(key === 'project_id' ? 'project' : key);
    }
    setSearchParams(newSearchParams);
  };

  const { data: requests, isLoading, error } = useFeatureRequests({
    ...filters,
    project_id: filters.project_id ? parseInt(filters.project_id) : undefined,
    sort_direction: 'desc'
  });
  
  const { data: projectsData } = useProjects();

  const createRequestMutation = useCreateFeatureRequest();

  const handleSubmitRequest = async (data: any) => {
    try {
      await createRequestMutation.mutateAsync(data);
      setShowForm(false);
    } catch (error) {
      console.error('Failed to submit feature request:', error);
    }
  };

  if (isLoading) {
    return (
      <div className="max-w-6xl mx-auto p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div className="text-center py-8">
          <p className="text-lg text-gray-600">Loading feature requests...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-6xl mx-auto p-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div className="text-center py-8">
          <p className="text-lg text-red-600">Error loading feature requests: {error.message}</p>
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
            Feature Requests
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
            Submit and vote on feature requests for WebHatchery projects
          </p>
        </div>
        <button
          onClick={() => setShowForm(true)}
          className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Submit Request
        </button>
      </div>

      {/* Request Form Modal */}
      {showForm && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
          <div className="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
                  Submit Feature Request
                </h2>
                <button
                  onClick={() => setShowForm(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  ×
                </button>
              </div>
              <FeatureRequestForm
                onSubmit={handleSubmitRequest}
                onCancel={() => setShowForm(false)}
              />
            </div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="mb-6 flex flex-wrap gap-2">
        <select 
          value={filters.project_id}
          onChange={(e) => updateFilter('project_id', e.target.value)}
          className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Projects</option>
          {getAllProjects(projectsData).map((project) => (
            <option key={project.id} value={project.id}>
              {project.title}
            </option>
          ))}
        </select>
        <select 
          value={filters.status}
          onChange={(e) => updateFilter('status', e.target.value)}
          className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Status</option>
          <option value="Open">Open</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="Closed">Closed</option>
        </select>
        <select 
          value={filters.priority}
          onChange={(e) => updateFilter('priority', e.target.value)}
          className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Priority</option>
          <option value="Critical">Critical</option>
          <option value="High">High</option>
          <option value="Medium">Medium</option>
          <option value="Low">Low</option>
        </select>
        <select 
          value={filters.category}
          onChange={(e) => updateFilter('category', e.target.value)}
          className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">All Categories</option>
          <option value="Bug Fix">Bug Fix</option>
          <option value="New Feature">New Feature</option>
          <option value="Enhancement">Enhancement</option>
          <option value="UI/UX Improvement">UI/UX Improvement</option>
        </select>
        <select 
          value={filters.sort_by}
          onChange={(e) => updateFilter('sort_by', e.target.value)}
          className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="votes">Sort by Votes</option>
          <option value="date">Sort by Date</option>
          <option value="priority">Sort by Priority</option>
        </select>
      </div>

      {/* Requests List */}
      <div className="space-y-4">
        {requests && requests.length > 0 ? (
          requests.map((request) => (
            <RequestCard
              key={request.id}
              title={request.title}
              description={request.description}
              votes={request.votes}
              status={request.status}
              priority={request.priority}
              category={request.category}
              tags={request.tags}
              date={request.created_at}
              project={request.project}
            />
          ))
        ) : (
          <div className="text-center py-8 text-gray-500">
            <p>No feature requests found.</p>
            <button
              onClick={() => setShowForm(true)}
              className="text-blue-600 hover:text-blue-800 underline mt-2"
            >
              Submit the first request!
            </button>
          </div>
        )}
      </div>

      {/* Load More */}
      <div className="mt-8 text-center">
        <button className="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
          Load More Requests
        </button>
      </div>
    </div>
  );
};

export default FeatureRequestsPage;