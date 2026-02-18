import React from 'react';
import { RankProgress } from '../types/Quest';

interface RankProgressBarProps {
    rankProgress: RankProgress | null;
}

const RANK_CONFIG: Record<string, { color: string; bg: string; border: string; icon: string }> = {
    Iron: { color: 'text-gray-700', bg: 'bg-gray-200', border: 'border-gray-300', icon: '🛡️' },
    Silver: { color: 'text-slate-600', bg: 'bg-slate-200', border: 'border-slate-300', icon: '⚔️' },
    Gold: { color: 'text-amber-700', bg: 'bg-amber-200', border: 'border-amber-300', icon: '👑' },
    Jade: { color: 'text-emerald-700', bg: 'bg-emerald-200', border: 'border-emerald-300', icon: '💎' },
    Diamond: { color: 'text-violet-700', bg: 'bg-violet-200', border: 'border-violet-300', icon: '🌟' },
};

const RANK_BAR_COLORS: Record<string, string> = {
    Iron: 'bg-gray-500',
    Silver: 'bg-slate-500',
    Gold: 'bg-amber-500',
    Jade: 'bg-emerald-500',
    Diamond: 'bg-gradient-to-r from-violet-500 to-purple-500',
};

export const RankProgressBar: React.FC<RankProgressBarProps> = ({ rankProgress }) => {
    if (!rankProgress) return null;

    const config = RANK_CONFIG[rankProgress.current_rank] || RANK_CONFIG.Iron;
    const barColor = RANK_BAR_COLORS[rankProgress.current_rank] || 'bg-gray-500';
    const isMaxRank = !rankProgress.next_rank;

    return (
        <div className={`bg-white rounded-lg shadow-sm border ${config.border} p-4 mb-6`}>
            <div className="flex items-center justify-between mb-2">
                <div className="flex items-center gap-2">
                    <span className="text-xl">{config.icon}</span>
                    <div>
                        <span className={`text-sm font-bold ${config.color}`}>
                            {rankProgress.current_rank} Rank
                        </span>
                        <span className="text-xs text-gray-500 ml-2">
                            {rankProgress.total_xp} XP • {rankProgress.completed_quests} quest{rankProgress.completed_quests !== 1 ? 's' : ''} completed
                        </span>
                    </div>
                </div>
                {rankProgress.next_rank && (
                    <div className="text-xs text-gray-500">
                        Next: <span className="font-semibold">{rankProgress.next_rank}</span>
                    </div>
                )}
            </div>

            <div className="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                <div
                    className={`h-full rounded-full transition-all duration-500 ease-out ${barColor}`}
                    style={{ width: `${Math.min(100, rankProgress.progress_percent)}%` }}
                />
            </div>

            <div className="flex justify-between mt-1.5">
                {isMaxRank ? (
                    <span className="text-[10px] text-violet-600 font-semibold">Maximum Rank Achieved!</span>
                ) : (
                    <>
                        <span className="text-[10px] text-gray-400">
                            {rankProgress.progress_percent}% to {rankProgress.next_rank}
                        </span>
                        <span className="text-[10px] text-gray-400">
                            {rankProgress.quests_needed > 0 && `${rankProgress.quests_needed} more quest${rankProgress.quests_needed !== 1 ? 's' : ''}`}
                            {rankProgress.quests_needed > 0 && rankProgress.xp_needed > 0 && ' • '}
                            {rankProgress.xp_needed > 0 && `${rankProgress.xp_needed} more XP`}
                        </span>
                    </>
                )}
            </div>
        </div>
    );
};
