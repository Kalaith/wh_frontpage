import React, { useEffect, useState } from 'react';
import { fetchCratePreview } from '../api/lootCrateApi';
import { LootTableInfo } from '../types/LootCrate';

const RARITY_STYLES: Record<string, { bg: string; border: string; text: string; glow: string; emoji: string }> = {
    common: { bg: 'bg-gray-100', border: 'border-gray-300', text: 'text-gray-700', glow: '', emoji: '📦' },
    uncommon: { bg: 'bg-green-50', border: 'border-green-400', text: 'text-green-700', glow: 'shadow-green-200', emoji: '🟢' },
    rare: { bg: 'bg-blue-50', border: 'border-blue-400', text: 'text-blue-700', glow: 'shadow-blue-300', emoji: '💎' },
    epic: { bg: 'bg-purple-50', border: 'border-purple-500', text: 'text-purple-700', glow: 'shadow-purple-400 shadow-lg', emoji: '🔮' },
    legendary: { bg: 'bg-yellow-50', border: 'border-yellow-500', text: 'text-yellow-700', glow: 'shadow-yellow-400 shadow-xl', emoji: '🌟' },
};

const LootCratesPage: React.FC = () => {
    const [lootTable, setLootTable] = useState<LootTableInfo | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchCratePreview()
            .then(setLootTable)
            .catch(() => {
                // Fallback mock data
                setLootTable({
                    rarity_weights: { common: '50%', uncommon: '30%', rare: '13%', epic: '5%', legendary: '2%' },
                    rewards: {
                        common: { xp: '10–30', title_chance: '25%' },
                        uncommon: { xp: '25–75', title_chance: '25%' },
                        rare: { xp: '50–200', badge_chance: '40%', title_chance: '25%' },
                        epic: { xp: '150–500', badge_chance: '40%', title_chance: '25%' },
                        legendary: { xp: '400–1000', badge_chance: '40%', title_chance: '25%' },
                    },
                });
            })
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[60vh]">
                <div className="animate-spin rounded-full h-12 w-12 border-4 border-purple-600 border-t-transparent"></div>
            </div>
        );
    }

    return (
        <div className="max-w-5xl mx-auto px-4 py-8">
            {/* Hero */}
            <div className="text-center mb-10">
                <h1 className="text-4xl font-extrabold text-gray-900 mb-3">
                    🎁 Loot Crates
                </h1>
                <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                    Every merged Pull Request has a chance to drop a Loot Crate. Open them to earn bonus XP, rare badges, exclusive titles, and powerful perks!
                </p>
            </div>

            {/* Animated Crate Display */}
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4 mb-12">
                {Object.entries(RARITY_STYLES).map(([rarity, style]) => (
                    <div
                        key={rarity}
                        className={`${style.bg} ${style.border} border-2 rounded-xl p-5 text-center transition-all duration-300 hover:scale-105 hover:-translate-y-1 cursor-default ${style.glow}`}
                    >
                        <div className="text-4xl mb-2 animate-bounce" style={{ animationDelay: `${Math.random() * 2}s` }}>
                            {style.emoji}
                        </div>
                        <div className={`text-sm font-bold capitalize ${style.text}`}>
                            {rarity}
                        </div>
                        <div className="text-xs text-gray-500 mt-1">
                            {lootTable?.rarity_weights[rarity] ?? '—'}
                        </div>
                    </div>
                ))}
            </div>

            {/* Loot Table */}
            <div className="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div className="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                    <h2 className="text-xl font-bold text-white flex items-center gap-2">
                        📋 Loot Table
                    </h2>
                    <p className="text-indigo-200 text-sm mt-1">Drop rates and potential rewards by rarity tier</p>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-gray-50 text-left">
                                <th className="px-6 py-3 font-semibold text-gray-600">Rarity</th>
                                <th className="px-6 py-3 font-semibold text-gray-600">Drop Rate</th>
                                <th className="px-6 py-3 font-semibold text-gray-600">XP Range</th>
                                <th className="px-6 py-3 font-semibold text-gray-600">Badge Chance</th>
                                <th className="px-6 py-3 font-semibold text-gray-600">Title Chance</th>
                            </tr>
                        </thead>
                        <tbody>
                            {lootTable && Object.entries(lootTable.rarity_weights).map(([rarity, weight]) => {
                                const style = RARITY_STYLES[rarity];
                                const rewards = lootTable.rewards[rarity] ?? {};
                                return (
                                    <tr key={rarity} className={`border-t border-gray-100 ${style.bg} hover:brightness-95 transition`}>
                                        <td className="px-6 py-3">
                                            <span className={`font-bold capitalize ${style.text}`}>
                                                {style.emoji} {rarity}
                                            </span>
                                        </td>
                                        <td className="px-6 py-3 font-mono">{weight}</td>
                                        <td className="px-6 py-3 font-mono">{rewards.xp ?? '—'}</td>
                                        <td className="px-6 py-3">{rewards.badge_chance ?? '—'}</td>
                                        <td className="px-6 py-3">{rewards.title_chance ?? '—'}</td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* How It Works */}
            <div className="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white rounded-xl shadow border border-gray-200 p-6 text-center">
                    <div className="text-3xl mb-3">🔀</div>
                    <h3 className="font-bold text-gray-900 mb-2">Merge a PR</h3>
                    <p className="text-sm text-gray-600">Every merged Pull Request has a chance to drop a Loot Crate alongside your base XP reward.</p>
                </div>
                <div className="bg-white rounded-xl shadow border border-gray-200 p-6 text-center">
                    <div className="text-3xl mb-3">🎲</div>
                    <h3 className="font-bold text-gray-900 mb-2">Roll the Dice</h3>
                    <p className="text-sm text-gray-600">Rarity is determined by weighted random chance. Epic and Legendary crates are extremely rare!</p>
                </div>
                <div className="bg-white rounded-xl shadow border border-gray-200 p-6 text-center">
                    <div className="text-3xl mb-3">🎁</div>
                    <h3 className="font-bold text-gray-900 mb-2">Open & Claim</h3>
                    <p className="text-sm text-gray-600">Open your crate to reveal bonus XP, rare badges, exclusive titles, or powerful perk tokens.</p>
                </div>
            </div>
        </div>
    );
};

export default LootCratesPage;
