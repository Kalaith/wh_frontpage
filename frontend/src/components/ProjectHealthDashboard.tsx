import React from 'react';
import { motion } from 'framer-motion';
import { useHealthSummary, useCriticalProjects, useRunHealthCheck } from '../hooks/useProjectHealth';
import type { ProjectHealth } from '../types/projectHealth';

const ProjectHealthDashboard: React.FC = () => {
  const { data: summaryData, isLoading: summaryLoading, error: summaryError } = useHealthSummary();
  const { data: criticalData } = useCriticalProjects();
  const runHealthCheck = useRunHealthCheck();

  if (summaryLoading) {
    return (
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-semibold mb-4">Ecosystem Health</h3>
        <div className="animate-pulse">
          <div className="h-4 bg-gray-200 rounded mb-2"></div>
          <div className="h-4 bg-gray-200 rounded mb-2"></div>
          <div className="h-4 bg-gray-200 rounded"></div>
        </div>
      </div>
    );
  }

  if (summaryError) {
    return (
      <div className="bg-white rounded-lg shadow-sm border p-6">
        <h3 className="text-lg font-semibold mb-4">Ecosystem Health</h3>
        <div className="text-red-600 text-sm">
          Unable to load health data
        </div>
      </div>
    );
  }

  const summary = summaryData?.data;
  const criticalProjects: ProjectHealth[] = criticalData?.data ?? [];

  if (!summary) {
    return null;
  }

  return (
    <motion.div
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      className="bg-white rounded-lg shadow-sm border p-6"
    >
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold flex items-center gap-2">
          <HealthStatusIcon status={summary.overall_status} />
          Ecosystem Health
        </h3>

        <button
          onClick={() => runHealthCheck.mutate()}
          disabled={runHealthCheck.isPending}
          className="px-3 py-1 text-xs bg-blue-50 text-blue-700 rounded hover:bg-blue-100 transition-colors disabled:opacity-50"
        >
          {runHealthCheck.isPending ? 'Checking...' : 'Refresh'}
        </button>
      </div>

      {/* Overall Status */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <StatusCard
          label="Total"
          value={summary.total_projects}
          color="bg-gray-100 text-gray-800"
        />
        <StatusCard
          label="Healthy"
          value={summary.healthy_projects}
          color="bg-green-100 text-green-800"
        />
        <StatusCard
          label="Warning"
          value={summary.warning_projects}
          color="bg-yellow-100 text-yellow-800"
        />
        <StatusCard
          label="Critical"
          value={summary.critical_projects}
          color="bg-red-100 text-red-800"
        />
      </div>

      {/* Critical Projects Alert */}
      {criticalProjects.length > 0 && (
        <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
          <h4 className="text-sm font-medium text-red-800 mb-2 flex items-center gap-1">
            <span className="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
            Critical Threats ({criticalProjects.length})
          </h4>
          <div className="space-y-2">
            {criticalProjects.slice(0, 3).map((project: ProjectHealth) => (
              <CriticalProjectCard key={project.project_name} project={project} />
            ))}
          </div>
        </div>
      )}

      {/* Urgent Recommendations */}
      {summary.urgent_recommendations && summary.urgent_recommendations.length > 0 && (
        <div className="mb-4">
          <h4 className="text-sm font-medium text-orange-600 mb-2">
            Urgent Recommendations
          </h4>
          <div className="space-y-2">
            {summary.urgent_recommendations.slice(0, 2).map((rec, index) => (
              <div key={index} className="p-3 bg-orange-50 border border-orange-200 rounded-lg">
                <p className="text-sm text-orange-800 font-medium">{rec.action}</p>
                <p className="text-xs text-orange-600 mt-1">{rec.details}</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Top Issues */}
      {summary.top_issues && summary.top_issues.length > 0 && (
        <div>
          <h4 className="text-sm font-medium text-gray-600 mb-2">
            Common Issues
          </h4>
          <div className="space-y-1">
            {summary.top_issues.slice(0, 3).map((issue, index) => (
              <div key={index} className="flex items-center gap-2 text-xs">
                <IssueIcon severity={issue.severity} />
                <span className="text-gray-700">{issue.message}</span>
                <span className="text-gray-500">({issue.project})</span>
              </div>
            ))}
          </div>
        </div>
      )}
    </motion.div>
  );
};

interface StatusCardProps {
  label: string;
  value: number;
  color: string;
}

const StatusCard: React.FC<StatusCardProps> = ({ label, value, color }) => (
  <div className="text-center">
    <div className={`text-lg font-bold ${color} rounded px-2 py-1`}>
      {value}
    </div>
    <div className="text-xs text-gray-600 mt-1">{label}</div>
  </div>
);

interface CriticalProjectCardProps {
  project: ProjectHealth;
}

const CriticalProjectCard: React.FC<CriticalProjectCardProps> = ({ project }) => (
  <div className="flex items-center justify-between">
    <div>
      <span className="text-sm font-medium text-red-800">{project.project_name}</span>
      <span className="text-xs text-red-600 ml-2">
        {project.issues.length} issue{project.issues.length !== 1 ? 's' : ''}
      </span>
    </div>
    <div className="text-xs text-red-600">
      Score: {project.score}/100
    </div>
  </div>
);

const HealthStatusIcon: React.FC<{ status: string }> = ({ status }) => {
  switch (status) {
    case 'healthy':
      return <span className="w-2 h-2 bg-green-500 rounded-full"></span>;
    case 'warning':
      return <span className="w-2 h-2 bg-yellow-500 rounded-full"></span>;
    case 'critical':
      return <span className="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>;
    default:
      return <span className="w-2 h-2 bg-gray-500 rounded-full"></span>;
  }
};

const IssueIcon: React.FC<{ severity: string }> = ({ severity }) => {
  switch (severity) {
    case 'critical':
      return <span className="text-red-500">🔴</span>;
    case 'warning':
      return <span className="text-yellow-500">🟡</span>;
    case 'info':
      return <span className="text-blue-500">🔵</span>;
    default:
      return <span className="text-gray-500">⚪</span>;
  }
};

export default ProjectHealthDashboard;