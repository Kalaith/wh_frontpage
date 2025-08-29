import React from 'react';
import { Link } from 'react-router-dom';

interface RequestCardProps {
  title?: string;
  description?: string;
  votes?: number;
  status?: string;
  priority?: string;
  category?: string;
  tags?: string[];
  date?: string;
  project?: {
    id: number;
    title: string;
    group_name?: string;
  };
}

const RequestCard: React.FC<RequestCardProps> = ({
  title = "Request Title",
  description = "Request description goes here. This would be a longer description explaining what the feature request is about.",
  votes = 15,
  status = "Open",
  priority = "Medium",
  category = "Enhancement",
  tags = ["UI"],
  date = "Jun 20, 2025",
  project
}) => (
  <div className="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow border-l-4 border-blue-500 overflow-hidden">
    <header className="p-6 pb-4 border-b border-gray-100">
      <div className="flex items-start justify-between">
        <h3 className="text-xl font-semibold text-blue-600">{title}</h3>
        <div className="flex flex-col items-center gap-1 text-center">
          <button className="px-2 py-1 text-xs border border-gray-300 rounded hover:bg-green-50 hover:text-green-600 transition-colors">
            ▲
          </button>
          <span className="text-sm font-medium text-gray-700">{votes}</span>
          <button className="px-2 py-1 text-xs border border-gray-300 rounded hover:bg-red-50 hover:text-red-600 transition-colors">
            ▼
          </button>
        </div>
      </div>
    </header>
    
    <div className="p-6 pt-4">
      <p className="text-gray-600 leading-relaxed mb-4">
        {description}
      </p>
      
      {project && (
        <div className="flex items-center gap-2 text-sm mb-4">
          <span className="text-gray-500">Project:</span>
          <Link 
            to={`/projects`} 
            className="text-blue-600 hover:text-teal-500 font-medium transition-colors"
          >
            {project.title}
          </Link>
          {project.group_name && (
            <span className="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded border border-gray-200">
              {project.group_name}
            </span>
          )}
        </div>
      )}
      
      <div className="flex flex-wrap items-center gap-2 text-xs">
        <span className={`px-2.5 py-1 rounded-full font-medium ${
          status === 'Open' 
            ? 'bg-green-100 text-green-700 border border-green-200' 
            : 'bg-gray-100 text-gray-700 border border-gray-200'
        }`}>
          {status}
        </span>
        <span className={`px-2.5 py-1 rounded-full font-medium ${
          priority === 'High' 
            ? 'bg-red-100 text-red-700 border border-red-200' 
            : priority === 'Medium' 
            ? 'bg-yellow-100 text-yellow-700 border border-yellow-200' 
            : 'bg-gray-100 text-gray-700 border border-gray-200'
        }`}>
          {priority} Priority
        </span>
        <span className="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200 font-medium">
          {category}
        </span>
        {Array.isArray(tags) && tags.map(tag => (
          <span key={tag} className="px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
            {tag}
          </span>
        ))}
        <span className="ml-auto text-gray-500">{date}</span>
      </div>
    </div>
  </div>
);

export default RequestCard;