/**
 * Debug utilities for Auth0 integration
 * Use these functions in browser console to troubleshoot auth issues
 */
import { getAuthToken, isAuthenticated } from './authToken';

// Make these available globally for debugging
declare global {
  interface Window {
    debugAuth: {
      testToken: () => Promise<void>;
      checkAuth: () => Promise<void>;
      testAPI: () => Promise<void>;
    };
  }
}

// Debug functions
const testToken = async () => {
  console.log('🧪 Testing Auth0 token retrieval...');
  try {
    const token = await getAuthToken();
    console.log('🧪 Token result:', token ? '✅ Token received' : '❌ No token');
    if (token) {
      console.log('🧪 Token preview:', `${token.substring(0, 50)}...`);
      // Decode JWT payload (base64)
      try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        console.log('🧪 Token payload:', payload);
      } catch (e) {
        console.log('🧪 Could not decode token payload');
      }
    }
  } catch (error) {
    console.error('🧪 Error testing token:', error);
  }
};

const checkAuth = async () => {
  console.log('🧪 Checking authentication status...');
  try {
    const authenticated = await isAuthenticated();
    console.log('🧪 Is authenticated:', authenticated ? '✅ Yes' : '❌ No');
  } catch (error) {
    console.error('🧪 Error checking auth:', error);
  }
};

const testAPI = async () => {
  console.log('🧪 Testing API call with current token...');
  try {
    const response = await fetch('/api/projects', {
      headers: {
        'Authorization': `Bearer ${await getAuthToken()}`
      }
    });
    console.log('🧪 API response status:', response.status);
    if (!response.ok) {
      const errorText = await response.text();
      console.log('🧪 API error:', errorText);
    } else {
      console.log('🧪 API call successful');
    }
  } catch (error) {
    console.error('🧪 API test error:', error);
  }
};

// Export debug utilities
export const debugAuth = {
  testToken,
  checkAuth,
  testAPI
};

// Make available globally in development
if (typeof window !== 'undefined' && import.meta.env.DEV) {
  window.debugAuth = debugAuth;
  console.log('🧪 Debug utilities available: window.debugAuth.testToken(), window.debugAuth.checkAuth(), window.debugAuth.testAPI()');
}