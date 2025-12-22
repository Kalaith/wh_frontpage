import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../stores/authStore';
import { featureRequestApi } from '../../api/featureRequestApi';

export const FeatureAuthStatus: React.FC = () => {
  const { isAuthenticated, user, isLoading, logout } = useAuth();

  const [isClaimingEggs, setIsClaimingEggs] = React.useState(false);

  const handleClaimDaily = async () => {
    if (!user) return;

    setIsClaimingEggs(true);
    try {
      const result = await featureRequestApi.claimDailyEggs();
      if (result.eggs_earned) {
        alert(`🥚 Claimed ${result.eggs_earned} eggs! Your balance is now ${result.new_balance || 0} eggs.`);
        // Note: The balance in the user object should be updated.
        // For simplicity, we can reload or manually update the store if needed.
        window.location.reload();
      } else {
        alert('Unable to claim daily eggs');
      }
    } catch (error: unknown) {
      console.error('Failed to claim daily eggs:', error);
      alert('Failed to claim daily eggs. Please try again.');
    } finally {
      setIsClaimingEggs(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center gap-2">
        <div className="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
        <span className="text-sm text-gray-600">Loading...</span>
      </div>
    );
  }

  if (isAuthenticated && user) {
    return (
      <div className="flex items-center gap-3">
        {/* Egg Balance */}
        <div className="flex items-center gap-2 bg-blue-50 px-3 py-1 rounded-lg">
          <span className="text-xl">🥚</span>
          <span className="font-semibold text-blue-600">{(user.egg_balance || 0).toLocaleString()}</span>
        </div>

        {/* Daily Reward Button */}
        {user.can_claim_daily && (
          <button
            onClick={handleClaimDaily}
            disabled={isClaimingEggs}
            className="px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center gap-1"
          >
            {isClaimingEggs ? (
              <>
                <div className="w-3 h-3 border border-white border-t-transparent rounded-full animate-spin" />
                Claiming...
              </>
            ) : (
              <>
                <span className="text-sm">🎁</span>
                Claim Daily
              </>
            )}
          </button>
        )}

        {/* User Info */}
        <div className="flex items-center gap-2">
          <div className="px-2 py-1 rounded text-sm">
            Hi {user.display_name || user.username}
            {user.role === 'admin' && (
              <span className="ml-1 text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded">
                Admin
              </span>
            )}
          </div>
          <Link
            to="/profile"
            className="px-2 py-1 text-sm bg-blue-100 hover:bg-blue-200 text-blue-700 rounded transition-colors"
          >
            Profile
          </Link>
          <button
            onClick={logout}
            className="px-2 py-1 text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 rounded transition-colors"
          >
            Logout
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="flex items-center gap-2">
      <Link
        to="/login"
        className="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors"
      >
        Login
      </Link>
      <Link
        to="/register"
        className="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded transition-colors flex items-center gap-1"
      >
        <span className="text-sm">🥚</span>
        Sign Up (Get 500 eggs!)
      </Link>
    </div>
  );
};

export default FeatureAuthStatus;