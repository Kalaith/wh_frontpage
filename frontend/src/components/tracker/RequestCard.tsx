import React from 'react';

interface RequestCardProps {
  title?: string;
  description?: string;
  votes?: number;
  status?: string;
  priority?: string;
  category?: string;
  tags?: string[];
  date?: string;
}

const RequestCard: React.FC<RequestCardProps> = ({
  title = "Request Title",
  description = "Request description goes here. This would be a longer description explaining what the feature request is about.",
  votes = 15,
  status = "Open",
  priority = "Medium",
  category = "Enhancement",
  tags = ["UI"],
  date = "Jun 20, 2025"
}) => (
  <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md space-y-4">
    <div className="flex items-start justify-between">
      <h3 className="font-semibold text-gray-900 dark:text-gray-100">{title}</h3>
      <div className="flex flex-col items-center gap-1 min-w-0 text-center">
        <button className="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-green-50 hover:text-green-700 dark:hover:bg-green-900/20 dark:hover:text-green-300 text-gray-700 dark:text-gray-300">
          ▲
        </button>
        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">{votes}</span>
        <button className="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20 dark:hover:text-red-300 text-gray-700 dark:text-gray-300">
          ▼
        </button>
      </div>
    </div>
    
    <p className="text-gray-600 dark:text-gray-400 text-sm">
      {description}
    </p>
    
    <div className="flex flex-wrap items-center gap-2 text-xs">
      <span className={`px-2.5 py-0.5 rounded-full ${
        status === 'Open' 
          ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' 
          : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
      }`}>
        {status}
      </span>
      <span className={`px-2.5 py-0.5 rounded-full ${
        priority === 'High' 
          ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' 
          : priority === 'Medium' 
          ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' 
          : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
      }`}>
        {priority} Priority
      </span>
      <span className="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
        {category}
      </span>
      {tags.map(tag => (
        <span key={tag} className="px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
          {tag}
        </span>
      ))}
      <span className="ml-auto text-gray-500 dark:text-gray-400">{date}</span>
    </div>
  </div>
);

export default RequestCard;