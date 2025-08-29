import React from 'react';
import { Badge } from './Badge';

export const ProjectLegend: React.FC = () => {
  return (
    <section className="bg-white rounded-lg shadow-sm p-6 mb-12 border-l-4 border-blue-500">
      <h3 className="text-xl font-semibold text-blue-600 text-center mb-6">
        Project Status Guide
      </h3>
      <div className="grid md:grid-cols-2 gap-8">
        <div>
          <h4 className="text-lg font-medium text-gray-900 mb-4">Development Status</h4>
          <div className="space-y-4">
            <div className="flex items-center gap-4">
              <Badge variant="status" className="status-non-working">
                🚧 Non-Working
              </Badge>
              <span className="text-sm text-gray-600">
                In development, not yet functional
              </span>
            </div>
            <div className="flex items-center gap-4">
              <Badge variant="status" className="status-mvp">
                ⚡ MVP
              </Badge>
              <span className="text-sm text-gray-600">
                Minimum viable product, basic features working
              </span>
            </div>
            <div className="flex items-center gap-4">
              <Badge variant="status" className="status-fully-working">
                ✅ Fully Working
              </Badge>
              <span className="text-sm text-gray-600">
                Version 1.0+, production ready
              </span>
            </div>
          </div>
        </div>
        <div>
          <h4 className="text-lg font-medium text-gray-900 mb-4">Development Stage</h4>
          <div className="space-y-4">
            <div className="flex items-center gap-4">
              <Badge variant="stage" className="stage-static">
                Static
              </Badge>
              <span className="text-sm text-gray-600">HTML/CSS/JS only</span>
            </div>
            <div className="flex items-center gap-4">
              <Badge variant="stage" className="stage-react">
                React
              </Badge>
              <span className="text-sm text-gray-600">Modern React frontend</span>
            </div>
            <div className="flex items-center gap-4">
              <Badge variant="stage" className="stage-backend">
                Backend
              </Badge>
              <span className="text-sm text-gray-600">API and database integration</span>
            </div>
            <div className="flex items-center gap-4">
              <Badge variant="stage" className="stage-auth">
                Auth
              </Badge>
              <span className="text-sm text-gray-600">User authentication system</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
