import { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { FeatureRequest } from '../../types/featureRequest';
import { useFeatureRequestUser } from '../../stores/featureRequestStore';

interface VoteModalProps {
  feature: FeatureRequest;
  onClose: () => void;
  onVote: (eggs: number) => void;
}

export const VoteModal = ({ feature, onClose, onVote }: VoteModalProps) => {
  const user = useFeatureRequestUser();
  const [eggAmount, setEggAmount] = useState(10);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const maxEggs = user?.egg_balance ?? 0;
  const quickAmounts = [10, 25, 50, 100];

  const handleVote = async () => {
    if (!user || eggAmount <= 0 || eggAmount > maxEggs) {
      setError('Invalid egg amount');
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      await onVote(eggAmount);
    } catch (error: unknown) {
      setError((error as Error).message || 'Failed to cast vote');
    } finally {
      setIsSubmitting(false);
    }
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
          className="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto"
        >
          {/* Header */}
          <div className="px-6 py-4 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-gray-900">
                Vote on Feature
              </h3>
              <button
                onClick={onClose}
                className="text-gray-400 hover:text-gray-600 transition-colors"
              >
                <svg
                  className="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>

          {/* Content */}
          <div className="px-6 py-4">
            {/* Feature Info */}
            <div className="mb-6">
              <h4 className="font-medium text-gray-900 mb-2">
                {feature.title}
              </h4>
              <p className="text-sm text-gray-600 line-clamp-3">
                {feature.description}
              </p>

              <div className="flex items-center gap-4 mt-3 text-sm text-gray-500">
                <span className="flex items-center gap-1">
                  <span className="text-base">🥚</span>
                  {feature.total_eggs.toLocaleString()} total eggs
                </span>
                <span className="flex items-center gap-1">
                  <span className="text-base">👥</span>
                  {feature.vote_count} votes
                </span>
              </div>
            </div>

            {/* Your Balance */}
            <div className="mb-6 p-3 bg-blue-50 rounded-lg">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium text-gray-700">
                  Your Egg Balance
                </span>
                <span className="flex items-center gap-1 font-semibold text-blue-600">
                  <span className="text-base">🥚</span>
                  {maxEggs.toLocaleString()}
                </span>
              </div>
            </div>

            {/* Egg Amount Input */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                How many eggs do you want to allocate?
              </label>

              {/* Quick Amount Buttons */}
              <div className="grid grid-cols-4 gap-2 mb-3">
                {quickAmounts.map(amount => (
                  <button
                    key={amount}
                    onClick={() => setEggAmount(amount)}
                    disabled={amount > maxEggs}
                    className={`px-3 py-2 text-sm rounded-md transition-colors ${
                      eggAmount === amount
                        ? 'bg-blue-600 text-white'
                        : amount > maxEggs
                          ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    }`}
                  >
                    {amount}
                  </button>
                ))}
              </div>

              {/* Custom Input */}
              <div className="relative">
                <span className="absolute left-3 top-1/2 transform -translate-y-1/2 text-lg">
                  🥚
                </span>
                <input
                  type="number"
                  min="1"
                  max={maxEggs}
                  value={eggAmount}
                  onChange={e =>
                    setEggAmount(
                      Math.max(
                        1,
                        Math.min(maxEggs, parseInt(e.target.value) || 1)
                      )
                    )
                  }
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter amount"
                />
              </div>

              <div className="mt-2 text-xs text-gray-500">
                You can vote with 1 to {maxEggs.toLocaleString()} eggs
              </div>
            </div>

            {/* Voting Impact */}
            <div className="mb-6 p-3 bg-green-50 rounded-lg">
              <h5 className="text-sm font-medium text-gray-700 mb-1">
                Voting Impact
              </h5>
              <p className="text-xs text-gray-600">
                Your {eggAmount} eggs will increase this feature's priority.
                Features with more eggs are more likely to be implemented.
              </p>
              <p className="text-xs text-gray-500 mt-1">
                New total: {(feature.total_eggs + eggAmount).toLocaleString()}{' '}
                eggs
              </p>
            </div>

            {/* Error Message */}
            {error && (
              <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <p className="text-sm text-red-600">{error}</p>
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
            <button
              onClick={onClose}
              disabled={isSubmitting}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 transition-colors disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              onClick={handleVote}
              disabled={isSubmitting || eggAmount <= 0 || eggAmount > maxEggs}
              className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              {isSubmitting ? (
                <>
                  <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Voting...
                </>
              ) : (
                <>
                  <span className="text-base">🥚</span>
                  Vote {eggAmount} Eggs
                </>
              )}
            </button>
          </div>
        </motion.div>
      </motion.div>
    </AnimatePresence>
  );
};
