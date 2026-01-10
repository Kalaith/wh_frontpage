import React, { useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';

export interface ToastMessage {
    id: string;
    type: 'success' | 'error' | 'info';
    message: string;
}

interface ToastProps {
    toast: ToastMessage;
    onDismiss: (id: string) => void;
}

const Toast: React.FC<ToastProps> = ({ toast, onDismiss }) => {
    useEffect(() => {
        const timer = setTimeout(() => {
            onDismiss(toast.id);
        }, 4000);
        return () => clearTimeout(timer);
    }, [toast.id, onDismiss]);

    const bgColor = {
        success: 'bg-green-600',
        error: 'bg-red-600',
        info: 'bg-blue-600',
    }[toast.type];

    const icon = {
        success: '✓',
        error: '✕',
        info: 'ℹ',
    }[toast.type];

    return (
        <motion.div
            initial={{ opacity: 0, y: -20, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -20, scale: 0.95 }}
            className={`${bgColor} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px] max-w-md`}
        >
            <span className="text-lg font-bold">{icon}</span>
            <p className="flex-1 text-sm">{toast.message}</p>
            <button
                onClick={() => onDismiss(toast.id)}
                className="text-white/80 hover:text-white transition-colors"
            >
                ✕
            </button>
        </motion.div>
    );
};

interface ToastContainerProps {
    toasts: ToastMessage[];
    onDismiss: (id: string) => void;
}

export const ToastContainer: React.FC<ToastContainerProps> = ({ toasts, onDismiss }) => {
    return (
        <div className="fixed top-4 right-4 z-[100] flex flex-col gap-2">
            <AnimatePresence>
                {toasts.map((toast) => (
                    <Toast key={toast.id} toast={toast} onDismiss={onDismiss} />
                ))}
            </AnimatePresence>
        </div>
    );
};

export default Toast;
