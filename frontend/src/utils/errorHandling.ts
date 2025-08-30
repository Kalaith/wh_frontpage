// Error handling utilities for consistent error processing across the application

import type { AppError } from '../types/common';

// Core error extraction utilities
export const getErrorMessage = (error: unknown, defaultMessage?: string): string => {
  if (error instanceof Error) return error.message;
  if (typeof error === 'object' && error !== null && 'message' in error) {
    return String((error as { message: unknown }).message);
  }
  if (typeof error === 'string') return error;
  return defaultMessage || String(error);
};

export const getErrorStatus = (error: unknown): number | undefined => {
  if (typeof error === 'object' && error !== null && 'status' in error) {
    const status = (error as { status: unknown }).status;
    return typeof status === 'number' ? status : undefined;
  }
  return undefined;
};

// Error type detection
export const isAuthError = (error: unknown): boolean => {
  const status = getErrorStatus(error);
  return status === 401 || status === 403;
};

export const isNetworkError = (error: unknown): boolean => {
  const message = getErrorMessage(error).toLowerCase();
  const status = getErrorStatus(error);
  return message.includes('network') || message.includes('fetch') || status === 0;
};

// Simplified error creation with predefined types
export type ErrorType = 'network' | 'auth' | 'validation' | 'server' | 'not_found' | 'unknown';

const ERROR_PRESETS: Record<ErrorType, Omit<AppError, 'details'>> = {
  network: { code: 'NETWORK_ERROR', message: 'Network connection failed. Please check your internet connection.', status: 0 },
  auth: { code: 'AUTH_ERROR', message: 'Authentication failed. Please log in again.', status: 401 },
  validation: { code: 'VALIDATION_ERROR', message: 'Invalid input data.', status: 400 },
  server: { code: 'SERVER_ERROR', message: 'Server error occurred. Please try again later.', status: 500 },
  not_found: { code: 'NOT_FOUND', message: 'Resource not found.', status: 404 },
  unknown: { code: 'UNKNOWN_ERROR', message: 'An unexpected error occurred.' }
};

export const createError = (
  type: ErrorType,
  customMessage?: string,
  details?: unknown
): AppError => {
  const error: AppError = { ...ERROR_PRESETS[type] };
  if (customMessage) error.message = customMessage;
  if (details !== undefined) error.details = details;
  return error;
};

// Convenience functions for common error types
export const createNetworkError = (message?: string): AppError =>
  createError('network', message);

export const createAuthError = (message?: string): AppError =>
  createError('auth', message);

export const createValidationError = (message?: string, details?: unknown): AppError =>
  createError('validation', message, details);

export const createServerError = (message?: string): AppError =>
  createError('server', message);

export const createNotFoundError = (resource?: string): AppError =>
  createError('not_found', resource ? `${resource} not found.` : undefined);

// Smart error handler that automatically detects error type
export const handleApiError = (error: unknown): AppError => {
  // Check if it's already an AppError
  if (typeof error === 'object' && error !== null && 'code' in error && 'message' in error) {
    return error as AppError;
  }

  // Auto-detect error type based on status or message
  const status = getErrorStatus(error);
  const message = getErrorMessage(error);

  if (isNetworkError(error)) {
    return createNetworkError();
  }

  if (status === 401 || status === 403) {
    return createAuthError();
  }

  if (status === 400 || status === 422) {
    return createValidationError(message);
  }

  if (status && status >= 500) {
    return createServerError();
  }

  if (status === 404) {
    return createNotFoundError();
  }

  // Fallback to unknown error with original message
  return createError('unknown', message);
};