// Common types used across the application

export interface AppError {
  code: string;
  message: string;
  status?: number;
  details?: unknown;
}

export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  error?: {
    code?: string;
    message: string;
    details?: unknown;
  } | string;
  message?: string;
  count?: number;
}

export interface AuthError {
  code: string;
  message: string;
  details?: unknown;
}
