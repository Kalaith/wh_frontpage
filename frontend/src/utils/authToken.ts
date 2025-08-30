/**
 * Authentication Token Management - Auth0 Only
 * Handles Auth0 token retrieval for API requests
 */

// Global reference to Auth0 hook - will be set by App component
let auth0Hook: any = null;

/**
 * Set the Auth0 hook reference for token retrieval
 */
export const setAuth0Client = (auth0: any): void => {
  auth0Hook = auth0;
  console.log('🔐 Auth0 hook set:', Object.keys(auth0));
};

/**
 * Get Auth0 access token for API requests
 */
export const getAuthToken = async (): Promise<string | null> => {
  console.log('🔐 getAuthToken called');
  
  if (!auth0Hook) {
    console.warn('❌ Auth0 hook not initialized');
    return null;
  }

  try {
    // Check if user is authenticated
    const isAuthenticated = auth0Hook.isAuthenticated;
    console.log('🔐 User authenticated:', isAuthenticated);
    
    if (!isAuthenticated) {
      console.warn('❌ User not authenticated');
      return null;
    }

    console.log('🔐 Attempting to get token silently...');
    console.log('🔐 Auth0 audience:', import.meta.env.VITE_AUTH0_AUDIENCE);
    
    // Get access token silently
    const token = await auth0Hook.getAccessTokenSilently({
      authorizationParams: {
        audience: import.meta.env.VITE_AUTH0_AUDIENCE,
        scope: 'openid profile email'
      }
    });

    console.log('✅ Got Auth0 token:', token ? `${token.substring(0, 20)}...` : 'null');
    return token;
  } catch (error) {
    console.error('❌ Failed to get Auth0 token:', error);
    return null;
  }
};

/**
 * Clear authentication (logout)
 */
export const clearAuthTokens = async (): Promise<void> => {
  if (auth0Hook) {
    try {
      await auth0Hook.logout({
        logoutParams: {
          returnTo: window.location.origin
        }
      });
    } catch (error) {
      console.warn('Failed to logout from Auth0:', error);
    }
  }
};

/**
 * Check if user is authenticated
 */
export const isAuthenticated = async (): Promise<boolean> => {
  if (!auth0Hook) {
    return false;
  }

  try {
    return auth0Hook.isAuthenticated;
  } catch (error) {
    console.warn('Failed to check Auth0 authentication:', error);
    return false;
  }
};