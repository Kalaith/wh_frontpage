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
  title = 'Suggestion Title',
  description = 'Suggestion description goes here.',
  group = 'Games',
  rationale = 'Would complement the existing game portfolio.',
  votes = 12,
  date = 'Jun 19, 2025',
}) => (
  <div className="bg-white p-6 rounded-lg shadow-md">
    <div className="flex items-start justify-between mb-3">
      <div>
        <h3 className="font-semibold text-gray-900">{title}</h3>
        <div className="text-sm text-blue-600">{group}</div>
      </div>
      <div className="flex items-center gap-2">
        <button className="px-2 py-1 text-xs border border-gray-300 rounded hover:bg-green-50 hover:text-green-700 text-gray-700">
          ▲
        </button>
        <span className="text-sm font-medium text-gray-700">{votes}</span>
        <button className="px-2 py-1 text-xs border border-gray-300 rounded hover:bg-red-50 hover:text-red-700 text-gray-700">
          ▼
        </button>
      </div>
    </div>
    <p className="text-gray-600 text-sm mb-2">{description}</p>
    <div className="flex gap-2 text-xs items-center text-gray-600">
      <strong className="text-gray-900">Rationale:</strong> {rationale}
      <span className="ml-auto text-gray-500">{date}</span>
    </div>
  </div>
);

export default SuggestionCard;
