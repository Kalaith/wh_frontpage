import { useState, useEffect, useCallback } from 'react';
import { motion } from 'framer-motion';
import { FeatureRequestCard } from '../components/features/FeatureRequestCard';
import { CreateFeatureModal } from '../components/features/CreateFeatureModal';
import { useAuth } from '../stores/authStore';
import { featureRequestApi } from '../api/featureRequestApi';
import api from '../api/api';
import type { FeatureRequest, CreateFeatureRequest } from '../types/featureRequest';
import type { Project } from '../types/projects';

export const FeatureRequestDashboard = () => {
  const [features, setFeatures] = useState<FeatureRequest[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<'all' | 'approved' | 'pending'>('approved');

  const { isAuthenticated, isLoading: authLoading, isAdmin, user, refreshUserInfo } = useAuth();

  const loadFeatures = useCallback(async () => {
    try {
      setIsLoading(true);
      setError(null);

      const params: {
        limit: number;
        sort_by: string;
        sort_direction: 'asc' | 'desc';
        status?: string;
      } = {
        limit: 50,
        sort_by: 'total_eggs',
        sort_direction: 'desc' as const
      };

      if (filter !== 'all') {
        params.status = filter;
      }

      const data = await featureRequestApi.getAllFeatures(params);
      setFeatures(data);
    } catch (error: unknown) {
      setError((error as Error).message || 'Failed to load features');
      console.error('Failed to load features:', error);
    } finally {
      setIsLoading(false);
    }
  }, [filter]);

  useEffect(() => {
    // Only load features when auth is complete and user is authenticated
    if (authLoading) return; // Wait for auth to complete
    if (!isAuthenticated) return; // Must be authenticated

    // Reset filter if non-admin user somehow has 'pending' selected
    if (filter === 'pending' && !isAdmin) {
      setFilter('approved');
      return;
    }
    loadFeatures();
  }, [filter, isAdmin, authLoading, isAuthenticated, loadFeatures]);

  useEffect(() => {
    // Only load projects when auth is complete (projects are public but we need consistent loading)
    if (authLoading) return; // Wait for auth to complete
    loadProjects();
  }, [authLoading]);

  const loadProjects = async () => {
    try {
      const response = await api.getProjects();
      if (response.success && response.data) {
        setProjects(response.data.projects || []);
      }
    } catch (error) {
      console.error('Failed to load projects:', error);
      // Don't show error for projects, it's optional
    }
  };

  const handleCreateFeature = async (data: CreateFeatureRequest) => {
    if (!user) throw new Error('Must be logged in');

    try {
      await featureRequestApi.createFeature({ ...data, user_id: user.id });
      await loadFeatures();
      await refreshUserInfo(); // Refresh user's egg balance
    } catch (error) {
      console.error('Failed to create feature:', error);
      throw error;
    }
  };

  const handleVote = async (featureId: number, eggs: number) => {
    if (!user) return;

    try {
      await featureRequestApi.voteOnFeature({
        user_id: user.id,
        feature_id: featureId,
        eggs_allocated: eggs,
      });
      await loadFeatures();
      await refreshUserInfo(); // Refresh user's egg balance
    } catch (error: unknown) {
      console.error('Failed to vote:', error);
      throw new Error((error as Error).message || 'Failed to cast vote');
    }
  };

  const handleApprove = async (featureId: number, notes?: string) => {
    try {
      await featureRequestApi.approveFeature(featureId, notes);
      await loadFeatures(); // Reload to show updated status
    } catch (error: unknown) {
      console.error('Failed to approve feature:', error);
      alert('Failed to approve feature: ' + ((error as Error).message || 'Unknown error'));
    }
  };

  const handleReject = async (featureId: number, notes?: string) => {
    try {
      await featureRequestApi.rejectFeature(featureId, notes);
      await loadFeatures(); // Reload to show updated status
    } catch (error: unknown) {
      console.error('Failed to reject feature:', error);
      alert('Failed to reject feature: ' + ((error as Error).message || 'Unknown error'));
    }
  };

  // Show loading spinner while auth is loading
  if (authLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="flex flex-col items-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
          <p className="text-gray-600">Loading authentication...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
          <div className="text-4xl mb-4">🥚</div>
          <h2 className="text-2xl font-bold text-gray-900 mb-4">Feature Request System</h2>
          <p className="text-gray-600 mb-6">
            Please log in to access the feature request system and start voting with your eggs!
          </p>
          <p className="text-sm text-gray-500">
            New users receive 500 welcome eggs + 100 eggs daily
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Action Bar */}
      <div className="flex items-center justify-between mb-8">
        <div className="flex items-center space-x-4">
          {/* Filter Options */}
        </div>

        {user && (
          <button
            onClick={() => setShowCreateModal(true)}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2"
          >
            <span className="text-lg">✨</span>
            Create Request (100 🥚)
          </button>
        )}
      </div>

      {/* Filters */}
      <div className="flex items-center gap-4 mb-6">
        <span className="text-sm font-medium text-gray-700">Filter:</span>
        {(['all', 'approved'] as const).map((filterOption) => (
          <button
            key={filterOption}
            onClick={() => setFilter(filterOption)}
            className={`px-3 py-1 text-sm rounded-md transition-colors ${filter === filterOption
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
          >
            {filterOption.charAt(0).toUpperCase() + filterOption.slice(1)}
          </button>
        ))}
        {isAdmin && (
          <button
            onClick={() => setFilter('pending')}
            className={`px-3 py-1 text-sm rounded-md transition-colors ${filter === 'pending'
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
          >
            Pending
          </button>
        )}
      </div>

      {/* Content */}
      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      ) : error ? (
        <div className="text-center py-12">
          <div className="text-red-500 mb-4">❌ {error}</div>
          <button
            onClick={loadFeatures}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Try Again
          </button>
        </div>
      ) : features.length === 0 ? (
        <div className="text-center py-12">
          <div className="text-6xl mb-4">🥚</div>
          <h3 className="text-xl font-semibold text-gray-900 mb-2">No features yet</h3>
          <p className="text-gray-600 mb-4">Be the first to create a feature request!</p>
          {user && (
            <button
              onClick={() => setShowCreateModal(true)}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              Create First Feature
            </button>
          )}
        </div>
      ) : (
        <div className="grid gap-6">
          {features.map((feature) => (
            <motion.div
              key={feature.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: Math.random() * 0.1 }}
            >
              <FeatureRequestCard
                feature={feature}
                onVote={handleVote}
                onApprove={isAdmin ? handleApprove : undefined}
                onReject={isAdmin ? handleReject : undefined}
                showProject={true}
                compact={false}
              />
            </motion.div>
          ))}
        </div>
      )}

      {/* Create Feature Modal */}
      {showCreateModal && (
        <CreateFeatureModal
          onClose={() => setShowCreateModal(false)}
          onCreate={handleCreateFeature}
          projects={projects.map(p => ({ id: p.id as number, title: p.title }))}
        />
      )}
    </div>
  );
};