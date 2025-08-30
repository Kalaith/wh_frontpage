// Error handling utilities for consistent error processing across the application

export const getErrorMessage = (error: unknown): string => {
  if (error instanceof Error) {
    return error.message;
  }
  if (typeof error === 'object' && error !== null && 'message' in error) {
    return String((error as { message: unknown }).message);
  }
  return String(error);
};

export const getErrorStatus = (error: unknown): number | undefined => {
  if (typeof error === 'object' && error !== null && 'status' in error) {
    const status = (error as { status: unknown }).status;
    return typeof status === 'number' ? status : undefined;
  }
  return undefined;
};

export const getErrorCode = (error: unknown): string | undefined => {
  if (typeof error === 'object' && error !== null && 'code' in error) {
    const code = (error as { code: unknown }).code;
    return typeof code === 'string' ? code : undefined;
  }
  return undefined;
};

export const isNetworkError = (error: unknown): boolean => {
  return getErrorMessage(error).toLowerCase().includes('network') ||
         getErrorMessage(error).toLowerCase().includes('fetch') ||
         getErrorStatus(error) === 0;
};

export const isAuthError = (error: unknown): boolean => {
  const status = getErrorStatus(error);
  return status === 401 || status === 403;
};