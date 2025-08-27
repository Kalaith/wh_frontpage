import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import * as authApi from '../api/authApi';

// Query keys
export const authKeys = {
  all: ['auth'] as const,
  user: () => [...authKeys.all, 'user'] as const,
  health: () => [...authKeys.all, 'health'] as const,
};

// Auth queries
export const useCurrentUser = () => {
  return useQuery({
    queryKey: authKeys.user(),
    queryFn: async () => {
      try {
        const user = await authApi.getCurrentUser();
        return user;
      } catch (error) {
        // If no token or invalid token, return null instead of throwing
        return null;
      }
    },
    retry: false,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

// Auth mutations
export const useLogin = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async ({ email, password }: { email: string; password: string }) => {
      const user = await authApi.login({ email, password });
      return user;
    },
    onSuccess: (user) => {
      // Update user cache
      queryClient.setQueryData(authKeys.user(), user);
    },
    onError: () => {
      // Clear user cache on login error
      queryClient.setQueryData(authKeys.user(), null);
    },
  });
};

export const useRegister = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async (userData: {
      email: string;
      password: string;
      firstName: string;
      lastName: string;
    }) => {
      const user = await authApi.register(userData);
      return user;
    },
    onSuccess: (user) => {
      // Update user cache
      queryClient.setQueryData(authKeys.user(), user);
    },
    onError: () => {
      // Clear user cache on register error
      queryClient.setQueryData(authKeys.user(), null);
    },
  });
};

export const useLogout = () => {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async () => {
      authApi.logout();
    },
    onSuccess: () => {
      // Clear all cached data
      queryClient.clear();
      // Specifically clear user data
      queryClient.setQueryData(authKeys.user(), null);
    },
  });
};