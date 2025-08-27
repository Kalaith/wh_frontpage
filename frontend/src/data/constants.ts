/**
 * Application constants
 */

// API Configuration
export const API_CONFIG = {
  BASE_URL:
    process.env.NODE_ENV === 'production' ? '' : 'http://localhost:8000',
  ENDPOINTS: {
    PROJECTS: '/api/projects',
    AUTH: '/api/auth',
    USERS: '/api/users',
  },
  TIMEOUTS: {
    DEFAULT: 10000, // 10 seconds
    UPLOAD: 30000, // 30 seconds
  },
} as const;

// Cache Configuration
export const CACHE_CONFIG = {
  PROJECTS_DURATION: 5 * 60 * 1000, // 5 minutes
  AUTH_DURATION: 24 * 60 * 60 * 1000, // 24 hours
  DEFAULT_DURATION: 60 * 1000, // 1 minute
} as const;

// UI Constants
export const UI_CONFIG = {
  ANIMATION: {
    DURATION: {
      SHORT: 150,
      MEDIUM: 300,
      LONG: 500,
    },
    EASING: {
      EASE_IN_OUT: 'cubic-bezier(0.4, 0, 0.2, 1)',
      EASE_OUT: 'cubic-bezier(0, 0, 0.2, 1)',
      EASE_IN: 'cubic-bezier(0.4, 0, 1, 1)',
    },
  },
  BREAKPOINTS: {
    SM: 640,
    MD: 768,
    LG: 1024,
    XL: 1280,
    '2XL': 1536,
  },
} as const;

// Application Metadata
export const APP_METADATA = {
  NAME: 'WebHatchery Frontpage',
  VERSION: '0.0.1',
  DESCRIPTION: 'WebHatchery Frontpage - React version of the main landing page',
  AUTHOR: 'WebHatchery',
} as const;

// Error Messages
export const ERROR_MESSAGES = {
  NETWORK: 'Network error occurred. Please check your connection.',
  AUTH: {
    INVALID_CREDENTIALS: 'Invalid email or password.',
    SESSION_EXPIRED: 'Your session has expired. Please log in again.',
    UNAUTHORIZED: 'You are not authorized to perform this action.',
    REGISTRATION_FAILED: 'Registration failed. Please try again.',
  },
  PROJECTS: {
    LOAD_FAILED: 'Failed to load projects data.',
    CREATE_FAILED: 'Failed to create project.',
    UPDATE_FAILED: 'Failed to update project.',
    DELETE_FAILED: 'Failed to delete project.',
  },
  GENERIC: 'An unexpected error occurred. Please try again.',
} as const;
