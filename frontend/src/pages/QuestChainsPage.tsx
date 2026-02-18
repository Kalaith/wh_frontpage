import React, { useEffect, useMemo, useState } from 'react';
import { fetchQuestChains } from '../api/questChainApi';
import { fetchAdventurer } from '../api/adventurerApi';
import { useAuth } from '../stores/authStore';
import { QuestChain, QuestChainStep, QuestRank } from '../types/QuestChain';

const CHAIN_ICONS: Record<string, string> = {
    'adventurer-guild-backend-foundation': '[Q]',
    'adventurer-guild-cutover-readiness': '[Q]',
    'adventurer-guild-backend-cutover-v1': '[R]',
    'the-hatchlings-path': '[Q]',
    'bug-hunter-saga': '[Q]',
    'the-architects-journey': '[Q]',
};

const RANK_ORDER: QuestRank[] = ['Iron', 'Silver', 'Gold', 'Jade', 'Diamond'];

const DIFFICULTY_LABELS: Record<number, string> = {
    1: 'Starter',
    2: 'Basic',
    3: 'Standard',
    4: 'Advanced',
    5: 'Raid',
};

function compareRanks(a: QuestRank, b: QuestRank): number {
    return RANK_ORDER.indexOf(a) - RANK_ORDER.indexOf(b);
}

function rankFromLevel(level: number): QuestRank {
    if (level >= 20) return 'Diamond';
    if (level >= 12) return 'Jade';
    if (level >= 7) return 'Gold';
    if (level >= 3) return 'Silver';
    return 'Iron';
}

function rankFromQuestLevel(level?: number): QuestRank {
    if (!level || level <= 1) return 'Iron';
    if (level === 2) return 'Silver';
    if (level === 3) return 'Gold';
    if (level === 4) return 'Jade';
    return 'Diamond';
}

function getStepQuestLevel(step: QuestChainStep): number | undefined {
    return step.quest_level ?? step.difficulty;
}

function getStepRank(step: QuestChainStep): QuestRank {
    return step.rank_required ?? rankFromQuestLevel(getStepQuestLevel(step));
}

function getChainRank(chain: QuestChain): QuestRank {
    if (chain.rank_required) {
        return chain.rank_required;
    }
    const stepRanks = chain.steps.map(getStepRank);
    if (stepRanks.length === 0) {
        return 'Iron';
    }
    return stepRanks.reduce((currentMax, rank) => (
        compareRanks(rank, currentMax) > 0 ? rank : currentMax
    ));
}

const QuestChainsPage: React.FC = () => {
    const [chains, setChains] = useState<QuestChain[]>([]);
    const [expanded, setExpanded] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [playerRank, setPlayerRank] = useState<QuestRank | null>(null);
    const { user, isAuthenticated } = useAuth();

    useEffect(() => {
        fetchQuestChains()
            .then(setChains)
            .catch(() => setChains([]))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        let mounted = true;

        if (!isAuthenticated || !user?.username) {
            setPlayerRank(null);
            return () => {
                mounted = false;
            };
        }

        fetchAdventurer(user.username)
            .then((adventurer) => {
                if (!mounted) return;
                setPlayerRank(rankFromLevel(adventurer.level));
            })
            .catch(() => {
                if (!mounted) return;
                setPlayerRank(null);
            });

        return () => {
            mounted = false;
        };
    }, [isAuthenticated, user?.username]);

    const rankBanner = useMemo(() => {
        if (!isAuthenticated) {
            return 'Sign in to check which quests are unlocked for your rank.';
        }
        if (!playerRank) {
            return 'Rank not linked yet. Quests still show required rank.';
        }
        return `Your Rank: ${playerRank}`;
    }, [isAuthenticated, playerRank]);

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[60vh]">
                <div className="animate-spin rounded-full h-12 w-12 border-4 border-indigo-600 border-t-transparent"></div>
            </div>
        );
    }

    return (
        <div className="max-w-5xl mx-auto px-4 py-8">
            <div className="text-center mb-10">
                <h1 className="text-4xl font-extrabold text-gray-900 mb-3">Quest Chains</h1>
                <p className="text-lg text-gray-600 max-w-3xl mx-auto">
                    Plain-language quests for players, with optional RuneSage notes when technical help is needed.
                </p>
                <p className="text-sm text-indigo-700 mt-3">{rankBanner}</p>
            </div>

            <div className="space-y-6">
                {chains.map((chain) => {
                    const icon = CHAIN_ICONS[chain.slug] || '[Q]';
                    const isOpen = expanded === chain.slug;
                    const totalStepXp = chain.steps.reduce((sum, s) => sum + s.xp, 0);
                    const isRaid = chain.type === 'raid' || chain.labels?.includes('type:raid');
                    const chainRank = getChainRank(chain);

                    return (
                        <div key={chain.slug} className="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                            <button
                                onClick={() => setExpanded(isOpen ? null : chain.slug)}
                                className="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-gray-50 transition"
                            >
                                <div className="flex items-start gap-4">
                                    <span className="text-2xl font-bold text-indigo-700">{icon}</span>
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900">{chain.name}</h2>
                                        <p className="text-sm text-gray-500 mt-1">{chain.description}</p>
                                        <div className="flex flex-wrap gap-2 mt-2">
                                            <span className={`text-xs px-2 py-0.5 rounded font-medium ${isRaid ? 'bg-red-100 text-red-700' : 'bg-indigo-100 text-indigo-700'}`}>
                                                {isRaid ? 'RAID' : 'QUEST CHAIN'}
                                            </span>
                                            <span className="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">
                                                Rank Required: {chainRank}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className="text-right hidden sm:block">
                                        <div className="text-xs text-gray-500">{chain.total_steps} quests</div>
                                        <div className="text-sm font-bold text-indigo-600">{totalStepXp + chain.reward_xp} XP total</div>
                                    </div>
                                    <svg
                                        className={`w-5 h-5 text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`}
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>

                            {isOpen && (
                                <div className="border-t border-gray-100 px-6 py-5 bg-gray-50 space-y-5">
                                    {(chain.entry_criteria?.length ?? 0) > 0 && (
                                        <section className="bg-white rounded-lg border border-amber-200 p-4">
                                            <h3 className="font-semibold text-amber-900 mb-2">Entry Requirements</h3>
                                            <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                                {chain.entry_criteria?.map((item, index) => (
                                                    <li key={index}>{item}</li>
                                                ))}
                                            </ul>
                                        </section>
                                    )}

                                    {(chain.go_no_go_gates?.length ?? 0) > 0 && (
                                        <section className="bg-white rounded-lg border border-red-200 p-4">
                                            <h3 className="font-semibold text-red-900 mb-2">Go / No-Go Gates</h3>
                                            <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                                {chain.go_no_go_gates?.map((item, index) => (
                                                    <li key={index}>{item}</li>
                                                ))}
                                            </ul>
                                        </section>
                                    )}

                                    <div className="space-y-4">
                                        {chain.steps.map((step, index) => {
                                            const questLevel = getStepQuestLevel(step);
                                            const stepRank = getStepRank(step);
                                            const isLocked = playerRank ? compareRanks(playerRank, stepRank) < 0 : false;

                                            return (
                                                <article key={step.id ?? `${chain.slug}-${index}`} className="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div>
                                                            <h4 className="font-semibold text-gray-900">{index + 1}. {step.title}</h4>
                                                            <p className="text-sm text-gray-600 mt-1">{step.description}</p>
                                                        </div>
                                                        <span className="text-xs font-mono text-indigo-700 bg-indigo-50 px-2 py-1 rounded whitespace-nowrap">+{step.xp} XP</span>
                                                    </div>

                                                    <div className="flex flex-wrap gap-2 mt-3">
                                                        {step.type && (
                                                            <span className="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700">{step.type}</span>
                                                        )}
                                                        <span className="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700">
                                                            Rank Required: {stepRank}
                                                        </span>
                                                        {questLevel && (
                                                            <span className="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                                Level {questLevel} {DIFFICULTY_LABELS[questLevel] ?? ''}
                                                            </span>
                                                        )}
                                                        {step.due_date && (
                                                            <span className="text-xs px-2 py-0.5 rounded bg-amber-100 text-amber-800">Due: {step.due_date}</span>
                                                        )}
                                                        {isLocked && (
                                                            <span className="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-700">
                                                                Locked: Requires {stepRank}
                                                            </span>
                                                        )}
                                                    </div>

                                                    {step.goal && <p className="text-sm text-gray-800 mt-3"><strong>Goal:</strong> {step.goal}</p>}

                                                    {(step.player_steps?.length ?? 0) > 0 && (
                                                        <div className="mt-3">
                                                            <p className="text-xs font-semibold text-gray-600 mb-1">Steps</p>
                                                            <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                                                {step.player_steps?.map((item, i) => <li key={i}>{item}</li>)}
                                                            </ul>
                                                        </div>
                                                    )}

                                                    {(step.done_when?.length ?? 0) > 0 && (
                                                        <div className="mt-3 border border-emerald-200 bg-emerald-50 rounded p-3">
                                                            <p className="text-xs font-semibold text-emerald-800 mb-1">Done When</p>
                                                            <ul className="list-disc list-inside text-sm text-emerald-900 space-y-1">
                                                                {step.done_when?.map((item, i) => <li key={i}>{item}</li>)}
                                                            </ul>
                                                        </div>
                                                    )}

                                                    {(step.proof_required?.length ?? 0) > 0 && (
                                                        <div className="mt-3">
                                                            <p className="text-xs font-semibold text-gray-600 mb-1">Proof Required</p>
                                                            <div className="flex flex-wrap gap-2">
                                                                {step.proof_required?.map((proof) => (
                                                                    <span key={proof} className="text-xs px-2 py-0.5 rounded bg-purple-100 text-purple-700">
                                                                        {proof}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {step.rs_brief && (
                                                        <div className="mt-3 border border-indigo-200 bg-indigo-50 rounded p-3">
                                                            <p className="text-xs font-semibold text-indigo-800 mb-1">RuneSage Brief (Optional)</p>
                                                            {step.rs_brief.context && <p className="text-sm text-indigo-900"><strong>Context:</strong> {step.rs_brief.context}</p>}
                                                            {step.rs_brief.constraints && <p className="text-sm text-indigo-900 mt-1"><strong>Constraints:</strong> {step.rs_brief.constraints}</p>}
                                                            {(step.rs_brief.technical_notes?.length ?? 0) > 0 && (
                                                                <ul className="list-disc list-inside text-sm text-indigo-900 mt-1 space-y-1">
                                                                    {step.rs_brief.technical_notes?.map((note, i) => <li key={i}>{note}</li>)}
                                                                </ul>
                                                            )}
                                                            {step.rs_brief.suggested_prompt && (
                                                                <p className="text-sm text-indigo-900 mt-1">
                                                                    <strong>Suggested Prompt:</strong> {step.rs_brief.suggested_prompt}
                                                                </p>
                                                            )}
                                                        </div>
                                                    )}
                                                </article>
                                            );
                                        })}
                                    </div>

                                    <div className="bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 rounded-lg p-4">
                                        <div className="font-bold text-yellow-900">Chain Completion Reward</div>
                                        <div className="text-sm text-yellow-700 space-x-3">
                                            <span>+{chain.reward_xp} XP</span>
                                            {chain.reward_badge_slug && <span>Badge: <strong>{chain.reward_badge_slug.replace(/-/g, ' ')}</strong></span>}
                                            {chain.reward_title && <span>Title: <strong>{chain.reward_title}</strong></span>}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {chains.length === 0 && (
                <div className="text-center py-16 text-gray-500">
                    <div className="text-4xl mb-3">[ ]</div>
                    <p>No quest chains available right now. Check back soon.</p>
                </div>
            )}
        </div>
    );
};

export default QuestChainsPage;
