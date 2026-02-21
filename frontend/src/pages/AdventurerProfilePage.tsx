import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { fetchAdventurer } from '../api/adventurerApi';
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

const AdventurerProfilePage: React.FC = () => {
    const { username } = useParams<{ username: string }>();
    const [adventurer, setAdventurer] = useState<Adventurer | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const load = async () => {
            if (!username) return;
            try {
                const data = await fetchAdventurer(username);
                setAdventurer(data);
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load adventurer');
            } finally {
                setLoading(false);
            }
        };
        load();
    }, [username]);

    if (loading) {
        return <div className="flex justify-center py-12"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div></div>;
    }

    if (error || !adventurer) {
        return (
            <div className="container mx-auto px-4 py-8 text-center text-red-600 bg-red-50 rounded-lg p-6">
                <p className="font-bold mb-2">Error loading profile</p>
                <p>{error ?? 'Adventurer not found'}</p>
                <Link to="/leaderboard" className="mt-4 inline-block text-indigo-600 hover:text-indigo-800 underline">Back to Leaderboard</Link>
            </div>
        );
    }

    const classIcon = CLASS_ICONS[adventurer.class] ?? '❓';
    const nextLevelXP = adventurer.level * 100 * 1.5; // Example XP curve
    const progressPercent = Math.min(100, (adventurer.xp_total % nextLevelXP) / nextLevelXP * 100);

    return (
        <div className="container mx-auto px-4 py-8">
            {/* Header / Hero */}
            <div className="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden mb-8">
                <div className="bg-slate-50 border-b border-gray-100 h-32 relative"></div>
                <div className="px-8 pb-8 flex flex-col md:flex-row items-end -mt-12 gap-6 relative">
                    <div className="w-24 h-24 bg-white rounded-full p-2 shadow-lg flex items-center justify-center text-5xl">
                        {classIcon}
                    </div>
                    <div className="flex-1 text-center md:text-left">
                        <h1 className="text-3xl font-bold text-gray-900">{adventurer.github_username}</h1>
                        <p className="text-gray-500 font-medium capitalize flex items-center gap-2 justify-center md:justify-start">
                            Level {adventurer.level} {adventurer.class.replace(/-/g, ' ')}
                            {adventurer.equipped_title && (
                                <span className="bg-indigo-100 text-indigo-800 text-xs px-2 py-0.5 rounded-full uppercase tracking-wide">
                                    {adventurer.equipped_title}
                                </span>
                            )}
                        </p>
                    </div>
                    <div className="text-center md:text-right">
                        <div className="text-4xl font-bold text-indigo-600">
                            {adventurer.xp_total.toLocaleString()} XP
                        </div>
                        <div className="text-xs text-gray-400 uppercase tracking-widest font-semibold mt-1">Total Experience</div>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
                {/* Left Column: Stats & Mastery */}
                <div className="space-y-8">
                    {/* XP Progress */}
                    <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                        <h3 className="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span>📈</span> Level Progress
                        </h3>
                        <div className="w-full bg-gray-200 rounded-full h-4 mb-2">
                            <div
                                className="bg-green-500 h-4 rounded-full transition-all duration-500"
                                style={{ width: `${progressPercent}%` }}
                            ></div>
                        </div>
                        <div className="flex justify-between text-xs text-gray-500">
                            <span>Current Level</span>
                            <span>Next Level</span>
                        </div>
                    </div>

                    {/* Habitat Mastery */}
                    <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                        <h3 className="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span>🏛️</span> Habitat Mastery
                        </h3>
                        {adventurer.mastery && adventurer.mastery.length > 0 ? (
                            <ul className="space-y-4">
                                {adventurer.mastery.map(m => (
                                    <li key={m.project_id} className="border-b border-gray-50 pb-2 last:border-0 last:pb-0">
                                        <div className="flex justify-between items-center mb-1">
                                            <span className="font-medium text-gray-800">{m.project_title}</span>
                                            <span className="text-xs font-bold bg-gray-100 text-gray-600 px-2 py-0.5 rounded">M{m.mastery_level}</span>
                                        </div>
                                        <p className="text-xs text-gray-500 flex justify-between">
                                            <span>{m.contributions} Contribs</span>
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="text-sm text-gray-500 italic">No mastery recorded yet.</p>
                        )}
                    </div>
                </div>

                {/* Right Column: Badges */}
                <div className="md:col-span-2">
                    <div className="bg-white rounded-lg shadow-sm p-6 border border-gray-100 h-full">
                        <h3 className="font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <span>🏅</span> Badges & Achievements
                        </h3>
                        {adventurer.badges && adventurer.badges.length > 0 ? (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                {adventurer.badges.map(b => (
                                    <div key={b.id} className="text-center p-4 border border-gray-100 rounded-lg bg-gray-50/50">
                                        <div className="text-4xl mb-2">🏆</div>
                                        <h4 className="font-bold text-sm text-gray-900">{b.badge_name}</h4>
                                        <p className="text-xs text-gray-500 mt-1">Earned {new Date(b.earned_at).toLocaleDateString()}</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-12 text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                                <p className="mb-2 text-3xl">📭</p>
                                <p>No badges earned yet.</p>
                                <p className="text-sm mt-2">Complete quests to earn your first badge!</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AdventurerProfilePage;
