import React from 'react';
import { Quest, QuestAcceptance } from '../types/Quest';

interface QuestCardProps {
    quest: Quest;
    onViewDetails?: (quest: Quest) => void;
    isBlocked?: boolean;
    acceptance?: QuestAcceptance | null;
}

const CLASS_ICONS: Record<string, string> = {
    'bug-hunter': '🐛',
    'patch-crafter': '🔧',
    'feature-smith': '⚒️',
    'doc-sage': '📜',
    'ux-alchemist': '🎨',
    'ops-ranger': '🏹',
    'test-summoner': '🧪',
};

const STATUS_BADGES: Record<string, { label: string; classes: string }> = {
    accepted: { label: 'In Progress', classes: 'bg-amber-100 text-amber-700' },
    submitted: { label: 'Submitted', classes: 'bg-blue-100 text-blue-700' },
    completed: { label: 'Completed ✓', classes: 'bg-emerald-100 text-emerald-700' },
    rejected: { label: 'Rejected', classes: 'bg-red-100 text-red-700' },
};

export const QuestCard: React.FC<QuestCardProps> = ({ quest, onViewDetails, isBlocked = false, acceptance }) => {
    const classIcon = CLASS_ICONS[quest.class] || '❓';
    const status = acceptance?.status ?? null;
    const statusBadge = status ? STATUS_BADGES[status] : null;

    const extraLabels = quest.labels
        .filter((l) => !l.name.startsWith('difficulty:') && !l.name.startsWith('xp:') && l.name !== 'quest')
        .slice(0, 6);

    const getButtonText = (): string => {
        if (!status) return 'View Details';
        if (status === 'completed') return 'View Completed';
        if (status === 'submitted') return 'View Submission';
        return 'Continue Quest';
    };

    const headerGradient = status === 'completed'
        ? 'from-emerald-500 to-teal-600'
        : status === 'accepted'
            ? 'from-amber-500 to-orange-600'
            : status === 'submitted'
                ? 'from-blue-500 to-sky-600'
                : 'from-indigo-600 to-purple-600';

    return (
        <div className={`bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden border flex flex-col h-full ${status === 'completed' ? 'border-emerald-200' : 'border-gray-100'
            }`}>
            <div className={`bg-gradient-to-r ${headerGradient} p-3 flex justify-between items-center text-white`}>
                <div className="flex-1 min-w-0">
                    <span className="font-bold text-sm tracking-wider truncate block">{quest.title}</span>
                    {statusBadge && (
                        <span className={`inline-block mt-1 text-[10px] px-1.5 py-0.5 rounded-full font-semibold ${statusBadge.classes}`}>
                            {statusBadge.label}
                        </span>
                    )}
                </div>
                <span className="bg-white/20 px-2 py-0.5 rounded text-xs font-bold ml-2 whitespace-nowrap">{quest.xp} XP</span>
            </div>

            <div className="p-4 flex-1 flex flex-col gap-3">
                <div className="flex items-start gap-3">
                    <div className="text-2xl bg-gray-50 p-2 rounded-lg" title={`Class: ${quest.class}`}>
                        {classIcon}
                    </div>
                    <div>
                        <p className="text-xs text-gray-500">#{quest.number}</p>
                        {quest.class_fantasy && (
                            <p className="text-xs text-gray-500 mt-1">Suggested class: {quest.class_fantasy}</p>
                        )}
                    </div>
                </div>

                {quest.body && <p className="text-sm text-gray-700 leading-relaxed">{quest.body}</p>}

                {quest.specific && (
                    <p className="text-sm text-gray-800"><strong>Specific:</strong> {quest.specific}</p>
                )}
                {quest.metric_target && (
                    <p className="text-sm text-gray-800"><strong>Target:</strong> {quest.metric_target}</p>
                )}

                <div className="flex flex-wrap gap-2">
                    {quest.dependency_type && (
                        <span className="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-700">
                            {quest.dependency_type}
                        </span>
                    )}
                    {isBlocked && (
                        <span className="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-700">
                            Locked
                        </span>
                    )}
                    {quest.due_date && (
                        <span className="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-800">Due: {quest.due_date}</span>
                    )}
                    {quest.risk_level && (
                        <span className="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-700">Risk: {quest.risk_level}</span>
                    )}
                    {extraLabels.map((label) => (
                        <span
                            key={label.name}
                            className="text-[10px] px-1.5 py-0.5 rounded border"
                            style={{
                                backgroundColor: `#${label.color}20`,
                                borderColor: `#${label.color}40`,
                                color: `#${label.color}`,
                            }}
                        >
                            {label.name}
                        </span>
                    ))}
                </div>

                {(quest.in_scope?.length ?? 0) > 0 && (
                    <div className="border border-emerald-200 bg-emerald-50 rounded p-2">
                        <p className="text-xs font-semibold text-emerald-800 mb-1">In Scope</p>
                        <ul className="list-disc list-inside text-xs text-emerald-900 space-y-0.5">
                            {quest.in_scope?.slice(0, 3).map((item, i) => <li key={i}>{item}</li>)}
                        </ul>
                    </div>
                )}

                {(quest.out_of_scope?.length ?? 0) > 0 && (
                    <div className="border border-orange-200 bg-orange-50 rounded p-2">
                        <p className="text-xs font-semibold text-orange-800 mb-1">Out of Scope</p>
                        <ul className="list-disc list-inside text-xs text-orange-900 space-y-0.5">
                            {quest.out_of_scope?.slice(0, 3).map((item, i) => <li key={i}>{item}</li>)}
                        </ul>
                    </div>
                )}

                {quest.rollback_plan && (
                    <p className="text-xs text-gray-700 bg-gray-50 border border-gray-200 rounded p-2">
                        <strong>Rollback:</strong> {quest.rollback_plan}
                    </p>
                )}

                {(quest.proof_required?.length ?? 0) > 0 && (
                    <div>
                        <p className="text-xs font-semibold text-gray-600 mb-1">Proof Required</p>
                        <div className="flex flex-wrap gap-1">
                            {quest.proof_required?.slice(0, 4).map((proof) => (
                                <span key={proof} className="text-[10px] px-1.5 py-0.5 rounded bg-purple-100 text-purple-700">
                                    {proof}
                                </span>
                            ))}
                        </div>
                    </div>
                )}

                <div className="mt-auto pt-2">
                    <button
                        type="button"
                        onClick={() => onViewDetails?.(quest)}
                        className={`block w-full text-center font-semibold py-2 px-4 rounded transition-colors text-sm ${status === 'completed'
                                ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-600'
                                : 'bg-gray-50 hover:bg-gray-100 text-indigo-600'
                            }`}
                    >
                        {getButtonText()}
                    </button>
                </div>
            </div>
        </div>
    );
};
