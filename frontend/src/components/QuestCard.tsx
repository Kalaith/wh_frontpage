import React from 'react';
import { Quest } from '../types/Quest';
import { Badge } from './Badge';

interface QuestCardProps {
    quest: Quest;
}

const CLASS_ICONS: Record<string, string> = {
    'bug-hunter': '🐞',
    'patch-crafter': '🩹',
    'feature-smith': '⚔️',
    'doc-sage': '📜',
    'ux-alchemist': '⚗️',
    'ops-ranger': '🛡️',
    'test-summoner': '🧪',
};

const DIFFICULTY_STARS = ['⭐', '⭐⭐', '⭐⭐⭐', '⭐⭐⭐⭐', '👑'];

export const QuestCard: React.FC<QuestCardProps> = ({ quest }) => {
    const classIcon = CLASS_ICONS[quest.class] || '⚔️';
    const difficultyDisplay = quest.difficulty > 0 && quest.difficulty <= 5
        ? DIFFICULTY_STARS[quest.difficulty - 1]
        : 'Unknown';

    return (
        <div className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden border border-gray-100 flex flex-col h-full">
            {/* Header with XP Badge */}
            <div className="bg-gradient-to-r from-indigo-600 to-purple-600 p-3 flex justify-between items-center text-white">
                <span className="font-bold text-sm tracking-wider uppercase">{difficultyDisplay} Quest</span>
                <span className="bg-white/20 px-2 py-0.5 rounded text-xs font-bold">{quest.xp} XP</span>
            </div>

            <div className="p-4 flex-1 flex flex-col">
                {/* Class & Title */}
                <div className="flex items-start gap-3 mb-3">
                    <div className="text-2xl bg-gray-50 p-2 rounded-lg" title={`Class: ${quest.class}`}>
                        {classIcon}
                    </div>
                    <div>
                        <h3 className="font-bold text-gray-900 leading-tight mb-1 line-clamp-2">
                            {quest.title}
                        </h3>
                        <p className="text-xs text-gray-500">#{quest.number}</p>
                    </div>
                </div>

                {/* Labels / Tags */}
                <div className="flex flex-wrap gap-1 mb-4">
                    {quest.labels
                        .filter(l => !l.name.startsWith('diff') && !l.name.startsWith('xp') && !l.name.startsWith('quest'))
                        .map(label => (
                            <span
                                key={label.name}
                                className="text-[10px] px-1.5 py-0.5 rounded border"
                                style={{
                                    backgroundColor: `#${label.color}20`,
                                    borderColor: `#${label.color}40`,
                                    color: `#${label.color}`
                                }}
                            >
                                {label.name}
                            </span>
                        ))}
                </div>

                <div className="mt-auto">
                    <a
                        href={quest.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="block w-full text-center bg-gray-50 hover:bg-gray-100 text-indigo-600 font-semibold py-2 px-4 rounded transition-colors text-sm"
                    >
                        Accept Quest ⚔️
                    </a>
                </div>
            </div>
        </div>
    );
};
