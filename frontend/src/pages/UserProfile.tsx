import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import { useAuth } from '../stores/authStore';
import { featureRequestApi } from '../api/featureRequestApi';

export const UserProfile: React.FC = () => {
  const { user, isAuthenticated, isLoading: authLoading, logout } = useAuth();

  const [isEditing, setIsEditing] = useState(false);
  const [editForm, setEditForm] = useState({
    display_name: '',
    username: ''
  });
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [deleteConfirmation, setDeleteConfirmation] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (user) {
      setEditForm({
        display_name: user.display_name || '',
        username: user.username || ''
      });
    }
  }, [user]);

  useEffect(() => {
    if (authLoading) return; // Wait for auth to complete
  }, [isAuthenticated, authLoading]);

  const handleEditToggle = () => {
    if (isEditing) {
      // Reset form if canceling
      if (user) {
        setEditForm({
          display_name: user.display_name || '',
          username: user.username || ''
        });
      }
    }
    setIsEditing(!isEditing);
    setError(null);
  };

  const handleSave = async () => {
    if (!user) return;

    try {
      setIsLoading(true);
      setError(null);
      // Use featureRequestApi directly since we removed the store method
      await featureRequestApi.updateProfile(editForm);
      window.location.reload(); // Simple refresh for now
      setIsEditing(false);
    } catch (err: unknown) {
      setError((err as Error).message || 'Failed to update profile');
    } finally {
      setIsLoading(false);
    }
  };

  const handleDeleteAccount = async () => {
    if (!user) return;
    if (deleteConfirmation !== user.username) {
      setError('Username confirmation does not match');
      return;
    }

    try {
      setIsLoading(true);
      setError(null);
      await featureRequestApi.deleteAccount();
      logout(); // This will clear the user session
      // Redirect will happen automatically due to auth state change
    } catch (err: unknown) {
      setError((err as Error).message || 'Failed to delete account');
    } finally {
      setIsLoading(false);
    }
  };

  // Show loading while auth is loading
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

  if (!isAuthenticated || !user) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
          <div className="text-4xl mb-4">🔒</div>
          <h2 className="text-2xl font-bold text-gray-900 mb-4">Access Denied</h2>
          <p className="text-gray-600 mb-6">
            Please log in to access your profile.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        className="bg-white rounded-lg shadow-sm border border-gray-200"
      >
        {/* Header */}
        <div className="px-6 py-4 border-b border-gray-200">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center">
                <span className="text-2xl font-bold text-white">
                  {user.display_name ? user.display_name[0].toUpperCase() : user.username[0].toUpperCase()}
                </span>
              </div>
              <div>
                <h1 className="text-2xl font-bold text-gray-900">
                  {user.display_name || user.username}
                </h1>
                <div className="flex items-center gap-2 mt-1">
                  <span className={`px-2 py-1 text-xs rounded-full ${user.role === 'admin'
                    ? 'bg-purple-100 text-purple-800'
                    : 'bg-blue-100 text-blue-800'
                    }`}>
                    {user.role === 'admin' ? '👑 Admin' : '👤 User'}
                  </span>
                  {user.is_verified && (
                    <span className="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                      ✓ Verified
                    </span>
                  )}
                </div>
              </div>
            </div>
            <button
              onClick={handleEditToggle}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            >
              {isEditing ? 'Cancel' : 'Edit Profile'}
            </button>
          </div>
        </div>

        {/* Error Display */}
        {error && (
          <div className="px-6 py-4 bg-red-50 border-b border-red-200">
            <p className="text-red-700 text-sm">{error}</p>
          </div>
        )}

        {/* Profile Content */}
        <div className="px-6 py-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Basic Information */}
            <div className="space-y-4">
              <h3 className="text-lg font-semibold text-gray-900">Basic Information</h3>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Display Name
                </label>
                {isEditing ? (
                  <input
                    type="text"
                    value={editForm.display_name}
                    onChange={(e) => setEditForm(prev => ({ ...prev, display_name: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter display name"
                  />
                ) : (
                  <p className="px-3 py-2 bg-gray-50 rounded-md text-gray-900">
                    {user.display_name || 'Not set'}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Username
                </label>
                {isEditing ? (
                  <input
                    type="text"
                    value={editForm.username}
                    onChange={(e) => setEditForm(prev => ({ ...prev, username: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter username"
                  />
                ) : (
                  <p className="px-3 py-2 bg-gray-50 rounded-md text-gray-900">
                    {user.username}
                  </p>
                )}
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Email
                </label>
                <p className="px-3 py-2 bg-gray-50 rounded-md text-gray-900">
                  {user.email}
                </p>
                <p className="text-xs text-gray-500 mt-1">Email cannot be changed</p>
              </div>

              {isEditing && (
                <button
                  onClick={handleSave}
                  disabled={isLoading}
                  className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors disabled:opacity-50"
                >
                  {isLoading ? 'Saving...' : 'Save Changes'}
                </button>
              )}
            </div>

            {/* Account Stats */}
            <div className="space-y-4">
              <h3 className="text-lg font-semibold text-gray-900">Account Stats</h3>

              <div className="bg-gradient-to-r from-yellow-50 to-orange-50 p-4 rounded-lg border border-yellow-200">
                <div className="flex items-center gap-2 mb-2">
                  <span className="text-2xl">🥚</span>
                  <span className="text-lg font-semibold text-gray-900">
                    {user.egg_balance.toLocaleString()} Eggs
                  </span>
                </div>
                {user.can_claim_daily && (
                  <p className="text-sm text-green-600">Daily eggs available to claim!</p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="bg-blue-50 p-3 rounded-lg">
                  <p className="text-sm text-gray-600">Features Created</p>
                  <p className="text-xl font-bold text-blue-600">
                    {user.stats?.features_created ?? 0}
                  </p>
                </div>
                <div className="bg-green-50 p-3 rounded-lg">
                  <p className="text-sm text-gray-600">Votes Cast</p>
                  <p className="text-xl font-bold text-green-600">
                    {user.stats?.votes_cast ?? 0}
                  </p>
                </div>
              </div>

              <div className="text-sm text-gray-500">
                Member since {new Date(user.member_since).toLocaleDateString()}
              </div>
            </div>
          </div>
        </div>

        {/* Danger Zone */}
        <div className="px-6 py-4 border-t border-gray-200 bg-red-50">
          <h3 className="text-lg font-semibold text-red-900 mb-2">Danger Zone</h3>
          <p className="text-sm text-red-700 mb-4">
            Once you delete your account, there is no going back. Please be certain.
          </p>
          <button
            onClick={() => setShowDeleteModal(true)}
            className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors"
          >
            Delete Account
          </button>
        </div>
      </motion.div>

      {/* Delete Account Modal */}
      {showDeleteModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            className="bg-white rounded-lg p-6 w-full max-w-md mx-4"
          >
            <div className="text-center mb-6">
              <div className="text-4xl mb-4">⚠️</div>
              <h3 className="text-xl font-bold text-red-900 mb-2">Delete Account</h3>
              <p className="text-gray-600">
                This action cannot be undone. This will permanently delete your account and all associated data.
              </p>
            </div>

            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Type your username <strong>{user.username}</strong> to confirm:
              </label>
              <input
                type="text"
                value={deleteConfirmation}
                onChange={(e) => {
                  setDeleteConfirmation(e.target.value);
                  setError(null);
                }}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-red-500 focus:border-red-500"
                placeholder={user.username}
              />
            </div>

            <div className="flex gap-3 justify-end">
              <button
                onClick={() => {
                  setShowDeleteModal(false);
                  setDeleteConfirmation('');
                  setError(null);
                }}
                className="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={handleDeleteAccount}
                disabled={isLoading || deleteConfirmation !== user.username}
                className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50"
              >
                {isLoading ? 'Deleting...' : 'Delete Account'}
              </button>
            </div>
          </motion.div>
        </div>
      )}
    </div>
  );
};