import { useState } from 'react';
import { motion } from 'framer-motion';
import { FeatureRequest } from '../../types/featureRequest';
import { VoteModal } from './VoteModal';
import { useIsFeatureAuthenticated } from '../../stores/featureRequestStore';

interface FeatureRequestCardProps {
  feature: FeatureRequest;
  onVote?: (featureId: number, eggs: number) => void;
  showProject?: boolean;
  compact?: boolean;
}

export const FeatureRequestCard = ({ 
  feature, 
  onVote, 
  showProject = true, 
  compact = false 
}: FeatureRequestCardProps) => {
  const [showVoteModal, setShowVoteModal] = useState(false);
  const isAuthenticated = useIsFeatureAuthenticated();

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-300';
      case 'approved': return 'bg-green-100 text-green-800 border-green-300';
      case 'in_progress': return 'bg-blue-100 text-blue-800 border-blue-300';
      case 'completed': return 'bg-purple-100 text-purple-800 border-purple-300';
      case 'rejected': return 'bg-red-100 text-red-800 border-red-300';
      default: return 'bg-gray-100 text-gray-800 border-gray-300';
    }
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'high': return 'bg-red-500';
      case 'medium': return 'bg-yellow-500';
      case 'low': return 'bg-green-500';
      default: return 'bg-gray-500';
    }
  };

  const canVote = feature.status === 'approved' && isAuthenticated;

  return (
    <>
      <motion.div
        layout
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        className={`bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 ${
          compact ? 'p-4' : 'p-6'
        }`}
      >
        {/* Header */}
        <div className="flex items-start justify-between gap-4 mb-3">
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-2 mb-2">
              <div className={`w-2 h-2 rounded-full ${getPriorityColor(feature.priority_level)}`} />
              <h3 className={`font-semibold text-gray-900 truncate ${compact ? 'text-sm' : 'text-lg'}`}>
                {feature.title}
              </h3>
            </div>
            
            {showProject && feature.project && (
              <p className="text-xs text-gray-500 mb-1">
                {feature.project.title}
              </p>
            )}
          </div>

          <div className="flex items-center gap-2 flex-shrink-0">
            <span className={`px-2 py-1 text-xs font-medium rounded-full border ${getStatusColor(feature.status)}`}>
              {feature.status.replace('_', ' ')}
            </span>
          </div>
        </div>

        {/* Description */}
        {!compact && (
          <p className="text-gray-600 text-sm line-clamp-2 mb-4">
            {feature.description}
          </p>
        )}

        {/* Stats */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-1">
              <span className="text-lg">🥚</span>
              <span className="font-semibold text-gray-900">{feature.total_eggs.toLocaleString()}</span>
              <span className="text-xs text-gray-500">eggs</span>
            </div>
            
            <div className="flex items-center gap-1">
              <span className="text-sm">👥</span>
              <span className="text-sm text-gray-600">{feature.vote_count}</span>
              <span className="text-xs text-gray-500">votes</span>
            </div>

            {feature.user && (
              <div className="text-xs text-gray-500">
                by {feature.user.display_name || feature.user.username}
              </div>
            )}
          </div>

          <div className="flex items-center gap-2">
            {canVote && (
              <button
                onClick={() => setShowVoteModal(true)}
                className="px-3 py-1 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-colors"
              >
                Vote
              </button>
            )}
            
            <span className="text-xs text-gray-400">
              {new Date(feature.created_at).toLocaleDateString()}
            </span>
          </div>
        </div>

        {/* Tags */}
        {Array.isArray(feature.tags) && feature.tags.length > 0 && (
          <div className="flex flex-wrap gap-1 mt-3 pt-3 border-t border-gray-100">
            {feature.tags.map((tag, index) => (
              <span
                key={index}
                className="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-md"
              >
                {tag}
              </span>
            ))}
          </div>
        )}

        {/* Use case and benefits (expanded view) */}
        {!compact && (feature.use_case || feature.expected_benefits) && (
          <div className="mt-4 pt-4 border-t border-gray-100">
            {feature.use_case && (
              <div className="mb-2">
                <h5 className="text-xs font-medium text-gray-700 mb-1">Use Case:</h5>
                <p className="text-xs text-gray-600 line-clamp-2">{feature.use_case}</p>
              </div>
            )}
            {feature.expected_benefits && (
              <div>
                <h5 className="text-xs font-medium text-gray-700 mb-1">Expected Benefits:</h5>
                <p className="text-xs text-gray-600 line-clamp-2">{feature.expected_benefits}</p>
              </div>
            )}
          </div>
        )}
      </motion.div>

      {showVoteModal && (
        <VoteModal
          feature={feature}
          onClose={() => setShowVoteModal(false)}
          onVote={(eggs) => {
            onVote?.(feature.id, eggs);
            setShowVoteModal(false);
          }}
        />
      )}
    </>
  );
};