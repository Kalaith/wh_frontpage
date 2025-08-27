/**
 * Data directory for static content
 *
 * This directory contains static data files such as:
 * - Constants and configuration data
 * - Mock data for development/testing
 * - Static JSON data files
 * - Type definitions for data structures
 *
 * Example usage:
 * - constants.ts - Application constants
 * - mockData.ts - Mock data for testing
 * - config.ts - Configuration objects
 */

// This file can be used to export data modules
export * from './constants';

// Example of constants that might be stored here
export const APP_CONSTANTS = {
  API_ENDPOINTS: {
    PROJECTS: '/api/projects',
    AUTH: '/api/auth',
  },
  CACHE_DURATION: 5 * 60 * 1000, // 5 minutes
  MAX_RETRIES: 3,
} as const;
