import React from 'react';
import { FeatureAuthStatus } from './features/FeatureAuthStatus';

export const AuthStatus: React.FC = () => {
  // Always use the unified egg-based authentication system
  return <FeatureAuthStatus />;
};

export default AuthStatus;
