import React from 'react';
import { Link } from 'react-router-dom';
import type { ProjectsData } from '../types/projects';
import { HatchlingMark } from './HatchlingMark';

export const Footer: React.FC<{ data?: ProjectsData; isLoading?: boolean }> = ({
  data,
}) => (
  <footer className="hatchery-footer">
    <div className="hatchery-footer-brand">
      <HatchlingMark />
      <div>
        <strong>WebHatchery</strong>
        <p>A little place for big what-ifs.</p>
      </div>
    </div>
    <nav aria-label="Footer">
      <Link to="/about">About</Link>
      <Link to="/updates">Hatch log</Link>
      {data?.global?.repository?.url && (
        <a
          href={data.global.repository.url}
          target="_blank"
          rel="noopener noreferrer"
        >
          GitHub ↗
        </a>
      )}
    </nav>
    <small>© {new Date().getFullYear()} WebHatchery.au</small>
  </footer>
);
