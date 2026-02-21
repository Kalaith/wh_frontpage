import React from 'react';
import { Quest, QuestAcceptance } from '../types/Quest';
import { DependencyStatus } from '../hooks/useQuestBoard';
import { RuneSagePromptBuilder } from './RuneSagePromptBuilder';

interface QuestDetailModalProps {
    quest: Quest;
    status: DependencyStatus | null;
    acceptance: QuestAcceptance | null;
    resolveDependencyTitle: (ref: string) => string;
    onClose: () => void;
    onAccept: (quest: Quest) => void;
    onSubmitPR: (quest: Quest) => void;
    onCancel: (quest: Quest) => void;
    accepting: boolean;
    submitting: boolean;
    canceling: boolean;
}

export const QuestDetailModal: React.FC<QuestDetailModalProps> = ({
    quest,
    status,
    acceptance,
    resolveDependencyTitle,
    onClose,
    onAccept,
    onSubmitPR,
    onCancel,
    accepting,
    submitting,
    canceling
}) => {
    if (!status) return null;

    const getModalActionButton = () => {
        if (status.blocked) {
            return (
                <button
                    type="button"
                    disabled
                    className="px-4 py-2 rounded font-semibold bg-gray-300 text-gray-600 cursor-not-allowed"
                >
                    Locked by Dependencies
                </button>
            );
        }

        if (!acceptance) {
            return (
                <button
                    type="button"
                    onClick={() => onAccept(quest)}
                    disabled={accepting}
                    className={`px-4 py-2 rounded font-semibold ${accepting ? 'bg-gray-400 text-white' : 'bg-indigo-600 text-white hover:bg-indigo-700'
                        }`}
                >
                    {accepting ? 'Accepting...' : '⚔️ Accept Quest'}
                </button>
            );
        }

        switch (acceptance.status) {
            case 'accepted':
                return (
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => onCancel(quest)}
                            disabled={canceling || submitting}
                            className={`px-4 py-2 rounded font-semibold border ${canceling || submitting
                                ? 'bg-gray-200 text-gray-500 border-gray-200 cursor-not-allowed'
                                : 'bg-white text-rose-700 border-rose-200 hover:bg-rose-50'
                                }`}
                        >
                            {canceling ? 'Canceling...' : 'Cancel Quest'}
                        </button>
                        <button
                            type="button"
                            onClick={() => onSubmitPR(quest)}
                            disabled={submitting || canceling}
                            className={`px-4 py-2 rounded font-semibold border ${submitting || canceling
                                ? 'bg-gray-300 text-gray-600 border-gray-300 cursor-not-allowed'
                                : 'bg-indigo-600 text-white border-indigo-700 hover:bg-indigo-700'
                                }`}
                        >
                            {submitting ? 'Submitting...' : '🛡️ Turn In Quest (Submit PR)'}
                        </button>
                    </div>
                );
            case 'submitted':
                return (
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => onCancel(quest)}
                            disabled={canceling}
                            className={`px-4 py-2 rounded font-semibold border ${canceling
                                ? 'bg-gray-200 text-gray-500 border-gray-200 cursor-not-allowed'
                                : 'bg-white text-rose-700 border-rose-200 hover:bg-rose-50'
                                }`}
                        >
                            {canceling ? 'Canceling...' : 'Cancel Quest'}
                        </button>
                        <button
                            type="button"
                            disabled
                            className="px-4 py-2 rounded font-semibold bg-blue-100 text-blue-700 border border-blue-200 cursor-not-allowed"
                        >
                            ⏳ Awaiting Review
                        </button>
                    </div>
                );
            case 'completed':
                return (
                    <button
                        type="button"
                        disabled
                        className="px-4 py-2 rounded font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200 cursor-not-allowed"
                    >
                        ✅ Completed
                    </button>
                );
            case 'rejected':
                return (
                    <button
                        type="button"
                        onClick={() => onAccept(quest)}
                        className="px-4 py-2 rounded font-semibold bg-rose-600 text-white hover:bg-rose-700"
                    >
                        🔄 Re-accept Quest
                    </button>
                );
            default:
                return null;
        }
    };

    return (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div className="bg-white w-full max-w-3xl rounded-xl shadow-2xl border border-gray-200 max-h-[90vh] overflow-y-auto">
                <div className="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-bold text-gray-900">{quest.title}</h2>
                        <p className="text-sm text-gray-500">{quest.quest_code ?? `Quest #${quest.number}`}</p>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="text-gray-500 hover:text-gray-700 text-sm font-medium"
                    >
                        Close
                    </button>
                </div>

                <div className="p-6 space-y-5">
                    <div className="flex flex-wrap gap-2">
                        {quest.rank_required && (
                            <span className="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-700">
                                Rank Required: {quest.rank_required}
                            </span>
                        )}
                        {(quest.quest_level || quest.difficulty) && (
                            <span className="text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-700">
                                Quest Level: {quest.quest_level ?? quest.difficulty}
                            </span>
                        )}
                        {quest.dependency_type && (
                            <span className="text-xs px-2 py-1 rounded bg-slate-100 text-slate-700">
                                Dependency: {quest.dependency_type}
                            </span>
                        )}
                        {quest.due_date && (
                            <span className="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">
                                Due: {quest.due_date}
                            </span>
                        )}
                        {acceptance && (
                            <span className={`text-xs px-2 py-1 rounded font-semibold ${acceptance.status === 'completed' ? 'bg-emerald-100 text-emerald-700'
                                : acceptance.status === 'submitted' ? 'bg-blue-100 text-blue-700'
                                    : acceptance.status === 'accepted' ? 'bg-amber-100 text-amber-700'
                                        : 'bg-red-100 text-red-700'
                                }`}>
                                Status: {acceptance.status.charAt(0).toUpperCase() + acceptance.status.slice(1)}
                            </span>
                        )}
                    </div>

                    {status.blocked && (
                        <div className="border border-rose-200 bg-rose-50 text-rose-800 rounded-lg p-3 text-sm">
                            <strong>🔒 Locked:</strong> Prerequisites required. {status.reason ? `(${status.reason})` : '(Hover/Click to view missing dependencies)'}
                        </div>
                    )}

                    {(quest.depends_on?.length ?? 0) > 0 && (
                        <section>
                            <h3 className="text-sm font-semibold text-gray-800 mb-1">Depends On</h3>
                            <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                {quest.depends_on?.map((depRef, index) => {
                                    const label = resolveDependencyTitle(depRef);
                                    const unresolved = status.unresolved.includes(label);
                                    return (
                                        <li key={`${quest.id}-dep-${index}`}>
                                            {label}
                                            {' '}
                                            <span className={`text-xs ${unresolved ? 'text-rose-600' : 'text-emerald-600'}`}>
                                                {unresolved ? '(Pending)' : '(Ready/Cleared)'}
                                            </span>
                                        </li>
                                    );
                                })}
                            </ul>
                            {quest.unlock_condition && (
                                <p className="text-xs text-gray-600 mt-2">Unlock condition: {quest.unlock_condition}</p>
                            )}
                        </section>
                    )}

                    {quest.body && (
                        <section>
                            <h3 className="text-sm font-semibold text-gray-800 mb-1">Overview</h3>
                            <p className="text-sm text-gray-700 leading-relaxed">{quest.body}</p>
                        </section>
                    )}

                    {quest.goal && (
                        <section>
                            <h3 className="text-sm font-semibold text-gray-800 mb-1">Goal</h3>
                            <p className="text-sm text-gray-700">{quest.goal}</p>
                        </section>
                    )}

                    {(quest.player_steps?.length ?? 0) > 0 && (
                        <section>
                            <h3 className="text-sm font-semibold text-gray-800 mb-1">Steps</h3>
                            <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                {quest.player_steps?.map((step, index) => (
                                    <li key={`${quest.id}-step-${index}`}>{step}</li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {(quest.done_when?.length ?? 0) > 0 && (
                        <section>
                            <h3 className="text-sm font-semibold text-gray-800 mb-1">Done When</h3>
                            <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                {quest.done_when?.map((item, index) => (
                                    <li key={`${quest.id}-done-${index}`}>{item}</li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {/* RuneSage Prompt Builder — replaces the old simple RS Brief display */}
                    {quest.rs_brief && (
                        <RuneSagePromptBuilder
                            rsBrief={quest.rs_brief}
                            questTitle={quest.title}
                        />
                    )}
                </div>

                <div className="border-t border-gray-100 px-6 py-4 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        onClick={onClose}
                        className="px-4 py-2 rounded border border-gray-200 text-gray-700 hover:bg-gray-50"
                    >
                        Back
                    </button>
                    {getModalActionButton()}
                </div>
            </div>
        </div>
    );
};
