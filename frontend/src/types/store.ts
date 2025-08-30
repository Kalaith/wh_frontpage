// Store-related type definitions

export interface StoreError {
  code: string;
  message: string;
  details?: unknown;
}

export interface LoadingState {
  isLoading: boolean;
  error: string | null;
}

export interface ApiError {
  status?: number;
  code?: string;
  message: string;
  details?: unknown;
}

export interface AsyncActionState {
  isLoading: boolean;
  error: StoreError | null;
  lastUpdated: number | null;
}