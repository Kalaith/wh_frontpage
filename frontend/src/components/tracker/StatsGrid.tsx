import React from 'react';

interface StatsGridProps {
  totalProjects: number;
  totalRequests: number;
  openRequests: number;
  completedRequests: number;
}

const StatsGrid: React.FC<StatsGridProps> = ({
  totalProjects,
  totalRequests,
  openRequests,
  completedRequests,
}) => (
  <div className="grid grid-cols-2 gap-4 md:grid-cols-4 md:gap-6">
    <div className="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-blue-500 text-center">
      <div className="text-3xl font-bold text-blue-600 mb-2">
        {totalProjects}
      </div>
      <div className="text-sm font-medium text-gray-700">Total Projects</div>
    </div>
    <div className="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-green-500 text-center">
      <div className="text-3xl font-bold text-green-600 mb-2">
        {totalRequests}
      </div>
      <div className="text-sm font-medium text-gray-700">Feature Requests</div>
    </div>
    <div className="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-orange-500 text-center">
      <div className="text-3xl font-bold text-orange-600 mb-2">
        {openRequests}
      </div>
      <div className="text-sm font-medium text-gray-700">Open Requests</div>
    </div>
    <div className="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-teal-500 text-center">
      <div className="text-3xl font-bold text-teal-600 mb-2">
        {completedRequests}
      </div>
      <div className="text-sm font-medium text-gray-700">Completed</div>
    </div>
  </div>
);

export default StatsGrid;
