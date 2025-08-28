import React, { useState } from 'react';
import { useFeatureRequestUser, useIsFeatureAuthenticated, useFeatureLogout, useFeatureClaimDailyEggs } from '../../stores/featureRequestStore';
import { AuthModal } from './AuthModal';

export const FeatureAuthStatus: React.FC = () => {
  const isAuthenticated = useIsFeatureAuthenticated();
  const user = useFeatureRequestUser();
  const logout = useFeatureLogout();
  const claimDailyEggs = useFeatureClaimDailyEggs();
  
  const [showAuthModal, setShowAuthModal] = useState(false);
  const [authMode, setAuthMode] = useState<'login' | 'register'>('login');
  const [isClaimingEggs, setIsClaimingEggs] = useState(false);

  const handleClaimDaily = async () => {
    setIsClaimingEggs(true);
    try {
      const result = await claimDailyEggs();
      if (result.success && result.eggsEarned) {
        // Show a nice notification
        alert(`🥚 Claimed ${result.eggsEarned} eggs! Your balance is now ${user?.egg_balance || 0} eggs.`);
      } else {
        alert(result.message || 'Unable to claim daily eggs');
      }
    } catch (error) {
      console.error('Failed to claim daily eggs:', error);
      alert('Failed to claim daily eggs. Please try again.');
    } finally {
      setIsClaimingEggs(false);
    }
  };

  const openLogin = () => {
    setAuthMode('login');
    setShowAuthModal(true);
  };

  const openRegister = () => {
    setAuthMode('register');
    setShowAuthModal(true);
  };

  if (isAuthenticated && user) {
    return (
      <div className="flex items-center gap-3">
        {/* Egg Balance */}
        <div className="flex items-center gap-2 bg-blue-50 px-3 py-1 rounded-lg">
          <span className="text-xl">🥚</span>
          <span className="font-semibold text-blue-600">{user.egg_balance.toLocaleString()}</span>
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
    <>
      <div className="flex items-center gap-2">
        <button
          onClick={openLogin}
          className="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors"
        >
          Login
        </button>
        <button
          onClick={openRegister}
          className="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-sm rounded transition-colors flex items-center gap-1"
        >
          <span className="text-sm">🥚</span>
          Sign Up (Get 500 eggs!)
        </button>
      </div>
      
      <AuthModal
        isOpen={showAuthModal}
        onClose={() => setShowAuthModal(false)}
        mode={authMode}
      />
    </>
  );
};

export default FeatureAuthStatus;