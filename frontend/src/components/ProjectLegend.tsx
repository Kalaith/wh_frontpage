import React from 'react';
import { Badge } from './Badge';

export const ProjectLegend: React.FC = () => {
  return (
    <section className="project-legend">
      <h3>Project Status Guide</h3>
      <div className="legend-grid">
        <div className="legend-category">
          <h4>Development Status</h4>
          <div className="legend-items">
            <div className="legend-item">
              <Badge variant="status" className="status-non-working">
                🚧 Non-Working
              </Badge>
              <span className="legend-desc">
                In development, not yet functional
              </span>
            </div>
            <div className="legend-item">
              <Badge variant="status" className="status-mvp">
                ⚡ MVP
              </Badge>
              <span className="legend-desc">
                Minimum viable product, basic features working
              </span>
            </div>
            <div className="legend-item">
              <Badge variant="status" className="status-fully-working">
                ✅ Fully Working
              </Badge>
              <span className="legend-desc">
                Version 1.0+, production ready
              </span>
            </div>
          </div>
        </div>
        <div className="legend-category">
          <h4>Development Stage</h4>
          <div className="legend-items">
            <div className="legend-item">
              <Badge variant="stage" className="stage-static">
                Static
              </Badge>
              <span className="legend-desc">HTML/CSS/JS only</span>
            </div>
            <div className="legend-item">
              <Badge variant="stage" className="stage-react">
                React
              </Badge>
              <span className="legend-desc">Modern React frontend</span>
            </div>
            <div className="legend-item">
              <Badge variant="stage" className="stage-backend">
                Backend
              </Badge>
              <span className="legend-desc">API and database integration</span>
            </div>
            <div className="legend-item">
              <Badge variant="stage" className="stage-auth">
                Auth
              </Badge>
              <span className="legend-desc">User authentication system</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
