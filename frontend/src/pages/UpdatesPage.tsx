import React from 'react';
import { Link } from 'react-router-dom';
import { LatestUpdates } from '../components/LatestUpdates';

const UpdatesPage: React.FC = () => (
  <div className="hatchery-log">
    <div className="hatchery-container">
      <Link to="/" className="hatchery-text-link">
        ← Back to projects
      </Link>
      <h1 className="mt-6 text-3xl font-bold">The hatch log</h1>
      <p className="mb-6 mt-2 text-slate-600">
        The latest update from each project, newest first.
      </p>
      <LatestUpdates showAll />
    </div>
  </div>
);

export default UpdatesPage;
