/**
 * Toast Store - Global state for toast notifications
 */
import { create } from 'zustand';
import type { ToastMessage } from '../components/ui/Toast';

interface ToastState {
    toasts: ToastMessage[];
    addToast: (type: ToastMessage['type'], message: string) => void;
    removeToast: (id: string) => void;
    success: (message: string) => void;
    error: (message: string) => void;
    info: (message: string) => void;
}

export const useToastStore = create<ToastState>((set) => ({
    toasts: [],

    addToast: (type, message) => {
        const id = Date.now().toString() + Math.random().toString(36).substr(2, 9);
        set((state) => ({
            toasts: [...state.toasts, { id, type, message }],
        }));
    },

    removeToast: (id) => {
        set((state) => ({
            toasts: state.toasts.filter((t) => t.id !== id),
        }));
    },

    success: (message) => {
        const id = Date.now().toString() + Math.random().toString(36).substr(2, 9);
        set((state) => ({
            toasts: [...state.toasts, { id, type: 'success', message }],
        }));
    },

    error: (message) => {
        const id = Date.now().toString() + Math.random().toString(36).substr(2, 9);
        set((state) => ({
            toasts: [...state.toasts, { id, type: 'error', message }],
        }));
    },

    info: (message) => {
        const id = Date.now().toString() + Math.random().toString(36).substr(2, 9);
        set((state) => ({
            toasts: [...state.toasts, { id, type: 'info', message }],
        }));
    },
}));
