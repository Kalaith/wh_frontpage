import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchLeaderboard } from '../api/leaderboardApi';
import { Adventurer } from '../types/Adventurer';

const CLASS_ICONS: Record<string, string> = {
    'bug-hunter': '🐞',
    'patch-crafter': '🩹',
    'feature-smith': '⚔️',
    'doc-sage': '📜',
    'ux-alchemist': '⚗️',
    'ops-ranger': '🛡️',
    'test-summoner': '🧪',
    'hatchling': '🐣'
};

const LeaderboardPage: React.FC = () => {
    const [adventurers, setAdventurers] = useState<Adventurer[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const load = async () => {
            try {
                const data = await fetchLeaderboard();
                setAdventurers(data);
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load leaderboard');
            } finally {
                setLoading(false);
            }
        };
        load();
    }, []);

    return (
        <div className="container mx-auto px-4 py-8">
            <header className="mb-8 text-center">
                <h1 className="text-4xl font-extrabold text-gray-900 mb-2">
                    <span className="text-transparent bg-clip-text bg-gradient-to-r from-yellow-500 to-amber-600">
                        Season 1: The Awakening
                    </span>
                </h1>
                <p className="text-lg text-gray-600">Global Rankings & Habitat Mastery</p>
            </header>

            {loading ? (
                <div className="flex justify-center py-12">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                </div>
            ) : error ? (
                <div className="text-center py-12 text-red-600 font-medium">
                    {error}
                </div>
            ) : adventurers.length === 0 ? (
                <div className="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-100">
                    <p className="text-xl text-gray-500 mb-2">The season has just begun.</p>
                    <p className="text-gray-400">Be the first to claim a quest and earn your place in history!</p>
                </div>
            ) : (
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adventurer</th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class</th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Level</th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total XP</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {adventurers.map((adv, idx) => (
                                <tr key={adv.id} className={idx < 3 ? 'bg-yellow-50/30' : ''}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {idx + 1}
                                        {idx === 0 && ' 👑'}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="flex items-center">
                                            <Link to={`/adventurers/${adv.github_username}`} className="text-sm font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                                {adv.github_username}
                                            </Link>
                                            {adv.equipped_title && (
                                                <span className="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                    {adv.equipped_title}
                                                </span>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-900 flex items-center gap-2">
                                            <span>{CLASS_ICONS[adv.class] || '❓'}</span>
                                            <span className="capitalize">{adv.class.replace(/-/g, ' ')}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        Lv {adv.level}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        {adv.xp_total.toLocaleString()} XP
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
};

export default LeaderboardPage;
