/**
 * Application-wide constants
 * Centralized configuration for magic numbers and common values
 */

export const CACHE_DURATION = {
  PROJECTS: 5 * 60 * 1000, // 5 minutes
  GC_TIME: 10 * 60 * 1000, // 10 minutes
} as const;

export const LIMITS = {
  FEATURE_REQUESTS_DEFAULT: 50,
  TOP_REQUESTS_FILTERED: 10,
  TOP_REQUESTS_DISPLAY: 3,
  ACTIVITY_FEED_FILTERED: 20,
  ACTIVITY_FEED_DISPLAY: 5,
} as const;

export const STATUS_VALUES = {
  OPEN: ['Open', 'open', 'pending'] as const,
  COMPLETED: ['Completed', 'completed'] as const,
} as const;