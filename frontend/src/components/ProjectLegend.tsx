import React from 'react';
import { Badge } from './Badge';

export const ProjectLegend: React.FC = () => {
  const statusItems: Array<[string, string, string]> = [
    ['status-planning', 'Planning', 'Design doc only'],
    ['status-non-working', 'In Development', 'In development'],
    ['status-mvp', 'MVP', 'Core features ready'],
    ['status-fully-working', 'Published', 'Production ready'],
  ];
  const stageItems: Array<[string, string, string]> = [
    ['stage-static', 'Static', 'HTML/CSS/JS'],
    ['stage-react', 'React', 'Frontend app'],
    ['stage-api', 'API', 'API layer'],
    ['stage-auth', 'Auth', 'Login system'],
  ];

  return (
    <section className="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
      <h3 className="text-lg font-semibold text-blue-600 text-center mb-4">
        Project Status Guide
      </h3>
      <div className="space-y-4">
        <div className="space-y-2">
          {statusItems.map(([className, label, description]) => (
            <div key={`status-${label}`} className="flex items-center justify-between gap-3">
              <Badge variant="status" className={className}>
                {label}
              </Badge>
              <span className="text-xs text-gray-600 text-right whitespace-nowrap">
                {description}
              </span>
            </div>
          ))}
        </div>
        <div className="space-y-2">
          {stageItems.map(([className, label, description]) => (
            <div key={`stage-${label}`} className="flex items-center justify-between gap-3">
              <Badge variant="stage" className={className}>
                {label}
              </Badge>
              <span className="text-xs text-gray-600 text-right whitespace-nowrap">
                {description}
              </span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};
