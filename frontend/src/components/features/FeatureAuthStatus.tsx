import React from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../../stores/authStore';
import { useToastStore } from '../../stores/toastStore';
import { featureRequestApi } from '../../api/featureRequestApi';

export const FeatureAuthStatus: React.FC = () => {
  const { isAuthenticated, user, isLoading, logout } = useAuth();
  const toast = useToastStore();

  const [isClaimingEggs, setIsClaimingEggs] = React.useState(false);

  const handleClaimDaily = async () => {
    if (!user) return;

    setIsClaimingEggs(true);
    try {
      const result = await featureRequestApi.claimDailyEggs();
      if (result.eggs_earned) {
        toast.success(`🥚 Claimed ${result.eggs_earned} eggs! Your balance is now ${result.new_balance || 0} eggs.`);
        // Note: The balance in the user object should be updated.
        // For simplicity, we can reload or manually update the store if needed.
        window.location.reload();
      } else {
        toast.info('Unable to claim daily eggs');
      }
    } catch (error: unknown) {
      console.error('Failed to claim daily eggs:', error);
      toast.error('Failed to claim daily eggs. Please try again.');
    } finally {
      setIsClaimingEggs(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center gap-2">
        <div className="w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full animate-spin"></div>
        <span className="text-sm text-slate-400">Loading...</span>
      </div>
    );
  }

  if (isAuthenticated && user) {
    return (
      <div className="flex items-center gap-3">
        {/* Egg Balance */}
        <div className="flex items-center gap-2 bg-slate-800 border border-slate-700 px-3 py-1 rounded-lg shadow-sm">
          <span className="text-xl filter drop-shadow">🥚</span>
          <span className="font-semibold text-cyan-400">{(user.egg_balance || 0).toLocaleString()}</span>
        </div>

        {/* Daily Reward Button */}
        {user.can_claim_daily && (
          <button
            onClick={handleClaimDaily}
            disabled={isClaimingEggs}
            className="px-3 py-1 bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-500 hover:to-green-400 text-white text-sm rounded-md shadow-lg shadow-green-500/20 transition-all disabled:opacity-50 flex items-center gap-1"
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
          <Link to="/profile" className="px-2 py-1 rounded text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
            Hi {user.display_name || user.username}
            {user.role === 'admin' && (
              <span className="ml-1 text-xs bg-purple-500/20 text-purple-300 px-2 py-0.5 rounded border border-purple-500/20">
                Admin
              </span>
            )}
          </Link>
          <button
            onClick={logout}
            className="px-2 py-1 text-sm text-slate-400 hover:text-red-400 hover:bg-red-500/10 rounded transition-colors"
          >
            Logout
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="flex items-center gap-3">
      <Link
        to="/login"
        className="px-4 py-1.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/50 rounded-md transition-colors"
      >
        Login
      </Link>
      <Link
        to="/register"
        className="px-4 py-1.5 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-500 hover:to-cyan-400 text-white text-sm font-medium rounded-md shadow-lg shadow-blue-500/20 transition-all flex items-center gap-1"
      >
        <span className="text-sm">🥚</span>
        Sign Up
      </Link>
    </div>
  );
};

export default FeatureAuthStatus;