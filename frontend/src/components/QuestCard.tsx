import React from 'react';
import { Quest, QuestAcceptance } from '../types/Quest';

interface QuestCardProps {
    quest: Quest;
    onViewDetails?: (quest: Quest) => void;
    isBlocked?: boolean;
    acceptance?: QuestAcceptance | null;
}

const RANK_CARD_STYLES: Record<string, string> = {
    iron: 'bg-stone-50 border-stone-200',
    silver: 'bg-slate-50 border-slate-200',
    gold: 'bg-amber-50 border-amber-200',
    jade: 'bg-emerald-50 border-emerald-200',
    diamond: 'bg-cyan-50 border-cyan-200',
};

const STATUS_BADGES: Record<string, { label: string; classes: string }> = {
    accepted: { label: 'In Progress', classes: 'bg-amber-100 text-amber-700' },
    submitted: { label: 'Submitted', classes: 'bg-blue-100 text-blue-700' },
    completed: { label: 'Completed', classes: 'bg-emerald-100 text-emerald-700' },
    rejected: { label: 'Rejected', classes: 'bg-red-100 text-red-700' },
};

export const QuestCard: React.FC<QuestCardProps> = ({ quest, onViewDetails, isBlocked = false, acceptance }) => {
    const status = acceptance?.status ?? null;
    const statusBadge = status ? STATUS_BADGES[status] : null;
    const questNumber = String(quest.number).padStart(4, '0');

    const level = quest.quest_level ?? quest.difficulty ?? 1;
    const derivedRank = level <= 1 ? 'Iron' : level === 2 ? 'Silver' : level === 3 ? 'Gold' : level === 4 ? 'Jade' : 'Diamond';
    const normalizedRank = String(quest.rank_required ?? derivedRank).toLowerCase();
    const cardRankStyle = RANK_CARD_STYLES[normalizedRank] ?? RANK_CARD_STYLES.iron;

    const extraLabels = quest.labels
        .filter((l) => !l.name.startsWith('difficulty:') && !l.name.startsWith('xp:') && l.name !== 'quest')
        .slice(0, 6);

    const toTitle = (value: string): string =>
        value
            .split('-')
            .filter(Boolean)
            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ');

    const normalizeLabel = (raw: string): string => {
        const [prefix, value] = raw.split(':');
        if (!value) return toTitle(raw);

        if (prefix === 'class') return '';
        if (prefix === 'type' || prefix === 'chain') {
            return toTitle(value);
        }
        return `${toTitle(prefix)}: ${toTitle(value)}`;
    };

    const formatDateLabel = (value: string): string => {
        const parsed = new Date(`${value}T00:00:00`);
        if (Number.isNaN(parsed.getTime())) {
            return value;
        }
        return parsed.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    };

    const metaTags: string[] = [];
    if (quest.dependency_type) {
        metaTags.push(toTitle(String(quest.dependency_type)));
    }
    if (quest.due_date) {
        metaTags.push(`Date: ${formatDateLabel(quest.due_date)}`);
    }
    if (quest.risk_level) {
        metaTags.push(`Risk: ${toTitle(String(quest.risk_level))}`);
    }
    extraLabels.forEach((label) => {
        const normalized = normalizeLabel(label.name);
        if (normalized && !metaTags.includes(normalized)) {
            metaTags.push(normalized);
        }
    });

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
        <div className={`rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden border flex flex-col h-full ${cardRankStyle}`}>
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
                    <div className="text-sm font-bold bg-gray-50 px-3 py-2 rounded-lg text-gray-700" title={`Quest #${quest.number}`}>
                        {questNumber}
                    </div>
                    <div>
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

                <div className="grid grid-cols-2 gap-2">
                    {metaTags.map((tag) => (
                        <span key={tag} className="text-xs px-2 py-1 rounded border border-gray-200 bg-white/70 text-gray-700 text-center">
                            {tag}
                        </span>
                    ))}
                    {isBlocked && (
                        <span className="text-xs px-2 py-1 rounded border border-rose-200 bg-rose-50 text-rose-700 text-center">
                            Locked
                        </span>
                    )}
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
