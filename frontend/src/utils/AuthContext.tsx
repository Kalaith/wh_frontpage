import React, { createContext, useContext, useState, useEffect, ReactNode, useCallback } from 'react';
import { useAuth0 } from '@auth0/auth0-react';
import type { User } from '../types/featureRequest';
import { setAuth0TokenGetter } from '../api/featureRequestApi';

// Define the shape of our auth context
interface AuthContextType {
  isAuthenticated: boolean;
  isLoading: boolean;
  isAdmin: boolean;
  user: User | null;
  checkingUserStatus: boolean;
  error: string | null;
  debugInfo: {
    auth0Email?: string;
    auth0Name?: string; 
    auth0Sub?: string;
    auth0Provider?: string;
    databaseEmail?: string;
    emailsMatch?: boolean;
    verificationState: string;
    lastError?: string;
    isSocialLogin?: boolean;
    logMessages: string[];
  };
  /**
   * Forces a re-verification of the authenticated user against the backend.
   * Use after completing signup so user data updates without full reload.
   */
  refreshUserInfo: () => Promise<void>;
  loginWithRedirect: () => void;
  logout: () => void;
}

// Create context with default values
const AuthContext = createContext<AuthContextType>({
  isAuthenticated: false,
  isLoading: true,
  isAdmin: false,
  user: null,
  checkingUserStatus: true,
  error: null,
  debugInfo: {
    verificationState: 'not_started',
    logMessages: []
  },
  refreshUserInfo: async () => { /* no-op default */ },
  loginWithRedirect: () => { /* no-op default */ },
  logout: () => { /* no-op default */ }
});

// Custom hook for using auth context
export const useAuth = () => useContext(AuthContext);

// Provider component
export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const auth0 = useAuth0();
  const { isAuthenticated, isLoading, user: auth0User, loginWithRedirect, logout: auth0Logout, getAccessTokenSilently } = auth0;
  
  // Add state for our extended auth information
  const [isAdmin, setIsAdmin] = useState<boolean>(false);
  const [user, setUser] = useState<User | null>(null);
  const [checkingUserStatus, setCheckingUserStatus] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [debugInfo, setDebugInfo] = useState<AuthContextType['debugInfo']>({
    verificationState: 'not_started',
    logMessages: []
  });

  /**
   * Helper to add a log message to the debug info
   * @param message The message to log
   * @param severity Log level: 'info', 'warn', or 'error'
   */
  const addLogMessage = (message: string, severity: 'info' | 'warn' | 'error' = 'info') => {
    const timestamp = new Date().toISOString();
    const formattedMessage = `[${timestamp}] [${severity.toUpperCase()}] 🔒 ${message}`;
    
    setDebugInfo(prev => ({
      ...prev,
      logMessages: [...prev.logMessages, formattedMessage].slice(-50) // Keep last 50 messages
    }));
    
    return formattedMessage;
  };

  /**
   * Verify user in our database and sync with Auth0 data
   */
  const performUserVerification = useCallback(async () => {
    if (!auth0User) return;

    addLogMessage('Starting user verification process');
    setCheckingUserStatus(true);
    
    try {
      // Get access token to make authenticated API call
      const token = await getAccessTokenSilently();
      
      // Call our API to verify/create user with timeout and retry logic
      let response: Response | null = null;
      let retryCount = 0;
      const maxRetries = 3;
      
      while (retryCount < maxRetries) {
        try {
          const controller = new AbortController();
          const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
          
          response = await fetch('/api/auth0/verify-user', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
              auth0_id: auth0User.sub,
              email: auth0User.email,
              display_name: auth0User.name || auth0User.email,
              username: auth0User.nickname || auth0User.email?.split('@')[0] || 'user'
            }),
            signal: controller.signal
          });
          
          clearTimeout(timeoutId);
          
          if (!response.ok) {
            throw new Error(`API call failed: ${response.status}`);
          }
          
          break; // Success, exit retry loop
          
        } catch (fetchError) {
          retryCount++;
          
          if (fetchError instanceof Error && fetchError.name === 'AbortError') {
            addLogMessage(`Request timeout (attempt ${retryCount}/${maxRetries})`, 'warn');
          } else {
            addLogMessage(`Network error (attempt ${retryCount}/${maxRetries}): ${fetchError instanceof Error ? fetchError.message : 'Unknown error'}`, 'warn');
          }
          
          if (retryCount >= maxRetries) {
            throw new Error(`Failed after ${maxRetries} attempts: ${fetchError instanceof Error ? fetchError.message : 'Unknown error'}`);
          }
          
          // Wait before retrying (exponential backoff)
          await new Promise(resolve => setTimeout(resolve, Math.pow(2, retryCount) * 1000));
        }
      }

      if (!response) {
        throw new Error('No response received after retries');
      }

      const result = await response.json();
      addLogMessage(`User verification result: ${result.success ? 'success' : 'failed'}`);

      if (result.success && result.data) {
        setUser(result.data);
        setIsAdmin(result.data.role === 'admin');
        addLogMessage(`User loaded: ${result.data.username} (${result.data.role})`);
        
        setDebugInfo(prev => ({
          ...prev,
          databaseEmail: result.data.email,
          emailsMatch: auth0User.email === result.data.email,
          verificationState: 'user_verified'
        }));
      } else {
        throw new Error(result.message || 'User verification failed');
      }

    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Failed to verify user';
      addLogMessage(`Error in user verification: ${errorMessage}`, 'error');
      setError(errorMessage);
      setDebugInfo(prev => ({
        ...prev,
        verificationState: 'verification_error',
        lastError: errorMessage
      }));
    } finally {
      setCheckingUserStatus(false);
    }
  }, [auth0User, getAccessTokenSilently]);

  // Set up Auth0 token getter for API calls
  useEffect(() => {
    if (isAuthenticated && !isLoading) {
      setAuth0TokenGetter(() => getAccessTokenSilently());
    }
  }, [isAuthenticated, isLoading, getAccessTokenSilently]);

  // Verify user in our database when they authenticate with Auth0
  useEffect(() => {
    addLogMessage(`Auth state changed - isAuthenticated: ${isAuthenticated}, isLoading: ${isLoading}, hasUser: ${!!auth0User}`);

    if (isAuthenticated && auth0User && !isLoading) {
      addLogMessage(`User authenticated with Auth0: ${auth0User.email}`);
      setDebugInfo(prev => ({
        ...prev,
        auth0Email: auth0User.email,
        auth0Name: auth0User.name,
        auth0Sub: auth0User.sub,
        isSocialLogin: auth0User.sub?.includes('|') || false,
        auth0Provider: auth0User.sub?.includes('|') ? auth0User.sub.split('|')[0] : 'auth0'
      }));
      performUserVerification();
    } else if (!isLoading) {
      const stateMessage = isAuthenticated ? 'Waiting for user data' : 'Not authenticated';
      addLogMessage(stateMessage);
      setDebugInfo(prev => ({
        ...prev,
        verificationState: isAuthenticated ? 'waiting_for_user_data' : 'not_authenticated'
      }));
      setCheckingUserStatus(false);
      // Clear user data when not authenticated
      setUser(null);
      setIsAdmin(false);
    }
  }, [isAuthenticated, auth0User, isLoading, performUserVerification]);

  const logout = useCallback(() => {
    addLogMessage('Logging out user');
    setUser(null);
    setIsAdmin(false);
    setError(null);
    auth0Logout({ logoutParams: { returnTo: window.location.origin } });
  }, [auth0Logout]);

  const contextValue: AuthContextType = {
    isAuthenticated,
    isLoading: isLoading || checkingUserStatus,
    isAdmin,
    user,
    checkingUserStatus,
    error,
    debugInfo,
    refreshUserInfo: performUserVerification,
    loginWithRedirect,
    logout
  };
  
  return (
    <AuthContext.Provider value={contextValue}>
      {children}
    </AuthContext.Provider>
  );
};