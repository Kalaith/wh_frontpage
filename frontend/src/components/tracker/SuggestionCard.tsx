import React from 'react';

interface SuggestionCardProps {
  title?: string;
  description?: string;
  group?: string;
  rationale?: string;
  votes?: number;
  date?: string;
}

const SuggestionCard: React.FC<SuggestionCardProps> = ({
  title = "Suggestion Title",
  description = "Suggestion description goes here.",
  group = "Games",
  rationale = "Would complement the existing game portfolio.",
  votes = 12,
  date = "Jun 19, 2025"
}) => (
  <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
    <div className="flex items-start justify-between mb-3">
      <div>
        <h3 className="font-semibold text-gray-900 dark:text-gray-100">{title}</h3>
        <div className="text-sm text-blue-600 dark:text-blue-400">{group}</div>
      </div>
      <div className="flex items-center gap-2">
        <button className="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-green-50 hover:text-green-700 dark:hover:bg-green-900/20 dark:hover:text-green-300 text-gray-700 dark:text-gray-300">▲</button>
        <span className="text-sm font-medium text-gray-700 dark:text-gray-300">{votes}</span>
        <button className="px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20 dark:hover:text-red-300 text-gray-700 dark:text-gray-300">▼</button>
      </div>
    </div>
    <p className="text-gray-600 dark:text-gray-400 text-sm mb-2">{description}</p>
    <div className="flex gap-2 text-xs items-center text-gray-600 dark:text-gray-400">
      <strong className="text-gray-900 dark:text-gray-100">Rationale:</strong> {rationale}
      <span className="ml-auto text-gray-500 dark:text-gray-400">{date}</span>
    </div>
  </div>
);

export default SuggestionCard;