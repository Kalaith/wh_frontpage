import { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { FeatureRequestCard } from '../components/features/FeatureRequestCard';
import { CreateFeatureModal } from '../components/features/CreateFeatureModal';
import { useFeatureRequestUser, useIsFeatureAuthenticated, useFeatureRefreshProfile } from '../stores/featureRequestStore';
import { featureRequestApi } from '../api/featureRequestApi';
import type { FeatureRequest, CreateFeatureRequest } from '../types/featureRequest';

export const FeatureRequestDashboard = () => {
  const [features, setFeatures] = useState<FeatureRequest[]>([]);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<'all' | 'approved' | 'pending'>('approved');

  const isAuthenticated = useIsFeatureAuthenticated();
  const user = useFeatureRequestUser();
  const refreshProfile = useFeatureRefreshProfile();

  useEffect(() => {
    loadFeatures();
  }, [filter]);

  const loadFeatures = async () => {
    try {
      setIsLoading(true);
      setError(null);
      
      const params: any = { 
        limit: 50,
        sort_by: 'total_eggs',
        sort_direction: 'desc' as const
      };
      
      if (filter !== 'all') {
        params.status = filter;
      }

      const data = await featureRequestApi.getAllFeatures(params);
      setFeatures(data);
    } catch (error: any) {
      setError(error.message || 'Failed to load features');
      console.error('Failed to load features:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleCreateFeature = async (data: CreateFeatureRequest) => {
    if (!user) throw new Error('Must be logged in');
    
    try {
      await featureRequestApi.createFeature({ ...data, user_id: user.id });
      await loadFeatures();
      await refreshProfile(); // Refresh user's egg balance
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
      await refreshProfile(); // Refresh user's egg balance
    } catch (error: any) {
      console.error('Failed to vote:', error);
      throw new Error(error.message || 'Failed to cast vote');
    }
  };

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
          {(['all', 'approved', 'pending'] as const).map((filterOption) => (
            <button
              key={filterOption}
              onClick={() => setFilter(filterOption)}
              className={`px-3 py-1 text-sm rounded-md transition-colors ${
                filter === filterOption
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              }`}
            >
              {filterOption.charAt(0).toUpperCase() + filterOption.slice(1)}
            </button>
          ))}
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
          />
        )}
    </div>
  );
};