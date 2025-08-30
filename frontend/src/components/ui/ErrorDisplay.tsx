/**
 * Reusable error display component
 */
import React from 'react';

interface ErrorDisplayProps {
  title?: string;
  message: string;
  onRetry?: () => void;
  retryLabel?: string;
}

const ErrorDisplay: React.FC<ErrorDisplayProps> = ({
  title = '❌ Error loading data',
  message,
  onRetry,
  retryLabel = 'Try Again'
}) => {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="text-center py-12">
        <div className="text-red-500 mb-4">{title}</div>
        <p className="text-gray-600 mb-4">{message}</p>
        {onRetry && (
          <button
            onClick={onRetry}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
          >
            {retryLabel}
          </button>
        )}
      </div>
    </div>
  );
};

export default ErrorDisplay;