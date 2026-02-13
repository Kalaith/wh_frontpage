import React, { useEffect, useState } from 'react';
import { fetchQuestChains } from '../api/questChainApi';
import { QuestChain } from '../types/QuestChain';

const CHAIN_ICONS: Record<string, string> = {
    'the-hatchlings-path': '🐣',
    'bug-hunter-saga': '🐞',
    'the-architects-journey': '🏗️',
};

const QuestChainsPage: React.FC = () => {
    const [chains, setChains] = useState<QuestChain[]>([]);
    const [expanded, setExpanded] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchQuestChains()
            .then(setChains)
            .catch(() => setChains([]))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[60vh]">
                <div className="animate-spin rounded-full h-12 w-12 border-4 border-indigo-600 border-t-transparent"></div>
            </div>
        );
    }

    return (
        <div className="max-w-4xl mx-auto px-4 py-8">
            <div className="text-center mb-10">
                <h1 className="text-4xl font-extrabold text-gray-900 mb-3">
                    ⛓️ Quest Chains
                </h1>
                <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                    Multi-step storylines that guide you through Web Hatchery. Complete every step to claim legendary rewards!
                </p>
            </div>

            <div className="space-y-6">
                {chains.map(chain => {
                    const icon = CHAIN_ICONS[chain.slug] || '📜';
                    const isOpen = expanded === chain.slug;
                    const totalStepXp = chain.steps.reduce((sum, s) => sum + s.xp, 0);

                    return (
                        <div key={chain.slug} className="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden transition-all duration-300">
                            {/* Header */}
                            <button
                                onClick={() => setExpanded(isOpen ? null : chain.slug)}
                                className="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-gray-50 transition"
                            >
                                <div className="flex items-center gap-4">
                                    <span className="text-3xl">{icon}</span>
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900">{chain.name}</h2>
                                        <p className="text-sm text-gray-500 mt-0.5">{chain.description}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-4">
                                    <div className="text-right hidden sm:block">
                                        <div className="text-xs text-gray-500">{chain.total_steps} steps</div>
                                        <div className="text-sm font-bold text-indigo-600">{totalStepXp + chain.reward_xp} XP total</div>
                                    </div>
                                    <svg className={`w-5 h-5 text-gray-400 transition-transform ${isOpen ? 'rotate-180' : ''}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>

                            {/* Steps (Expandable) */}
                            {isOpen && (
                                <div className="border-t border-gray-100 px-6 py-4 bg-gray-50">
                                    <div className="relative">
                                        {/* Vertical line */}
                                        <div className="absolute left-4 top-3 bottom-3 w-0.5 bg-indigo-200"></div>

                                        <div className="space-y-4">
                                            {chain.steps.map((step, i) => (
                                                <div key={i} className="flex items-start gap-4 relative">
                                                    {/* Step dot */}
                                                    <div className="w-8 h-8 rounded-full bg-indigo-100 border-2 border-indigo-400 flex items-center justify-center text-xs font-bold text-indigo-700 flex-shrink-0 z-10">
                                                        {i + 1}
                                                    </div>
                                                    <div className="flex-1 bg-white rounded-lg p-3 shadow-sm border border-gray-100">
                                                        <div className="flex justify-between items-center">
                                                            <h4 className="font-semibold text-gray-900">{step.title}</h4>
                                                            <span className="text-xs font-mono text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">+{step.xp} XP</span>
                                                        </div>
                                                        <p className="text-sm text-gray-600 mt-0.5">{step.description}</p>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Completion Reward */}
                                    <div className="mt-6 bg-gradient-to-r from-yellow-50 to-amber-50 border border-yellow-200 rounded-lg p-4 flex items-center gap-4">
                                        <span className="text-2xl">🏆</span>
                                        <div>
                                            <div className="font-bold text-yellow-900">Chain Completion Reward</div>
                                            <div className="text-sm text-yellow-700 space-x-3">
                                                <span>+{chain.reward_xp} XP</span>
                                                {chain.reward_badge_slug && <span>🏅 Badge: <strong>{chain.reward_badge_slug.replace(/-/g, ' ')}</strong></span>}
                                                {chain.reward_title && <span>🎖️ Title: <strong>{chain.reward_title}</strong></span>}
                                            </div>
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
                    <div className="text-4xl mb-3">📜</div>
                    <p>No quest chains available right now. Check back soon!</p>
                </div>
            )}
        </div>
    );
};

export default QuestChainsPage;
