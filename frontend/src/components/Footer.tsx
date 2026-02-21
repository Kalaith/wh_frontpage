import React from 'react';
import type { ProjectsData } from '../types/projects';

interface FooterProps {
  data?: ProjectsData;
  isLoading?: boolean;
}

export const Footer: React.FC<FooterProps> = ({ data, isLoading }) => {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="mt-16 pt-8 border-t border-gray-200 text-center">
      <div className="text-gray-600">
        <p className="mb-2">
          &copy; {currentYear} WebHatchery.au · All rights reserved
        </p>
        {isLoading || !data ? (
          <div className="mt-2 animate-pulse">
            <div className="h-4 w-64 bg-gray-100 rounded mx-auto"></div>
          </div>
        ) : (
          data.global?.buildTools && (
            <div className="mt-2">
              <small className="text-sm text-gray-500">
                Platform Requirements:{' '}
                {Object.entries(data.global.buildTools).map(
                  ([tool, version], index) => (
                    <span key={tool} className="font-medium">
                      {index > 0 && ' · '}
                      {tool} {version}
                    </span>
                  )
                )}
              </small>
            </div>
          )
        )}
      </div>
    </footer>
  );
};
