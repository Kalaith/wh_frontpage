/**
 * Authentication Token Management - Auth0 Only
 * Handles Auth0 token retrieval for API requests
 */

// Global reference to Auth0 hook - will be set by App component
let auth0Hook: unknown = null;

/**
 * Set the Auth0 hook reference for token retrieval
 */
export const setAuth0Client = (auth0: unknown): void => {
  auth0Hook = auth0;
  console.log('🔐 Auth0 hook set:', Object.keys(auth0 as Record<string, unknown>));
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
    const isAuthenticated = (auth0Hook as { isAuthenticated: boolean }).isAuthenticated;
    console.log('🔐 User authenticated:', isAuthenticated);

    if (!isAuthenticated) {
      console.warn('❌ User not authenticated');
      return null;
    }

    console.log('🔐 Attempting to get token silently...');
    console.log('🔐 Auth0 audience:', import.meta.env.VITE_AUTH0_AUDIENCE);

    // Get access token silently
    const token = await (auth0Hook as { getAccessTokenSilently: (params: unknown) => Promise<string> }).getAccessTokenSilently({
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
      await (auth0Hook as { logout: (params: unknown) => Promise<void> }).logout({
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
    return (auth0Hook as { isAuthenticated: boolean }).isAuthenticated;
  } catch (error) {
    console.warn('Failed to check Auth0 authentication:', error);
    return false;
  }
};