import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../stores/authStore';
import { fetchAdventurer } from '../api/adventurerApi';
import { Adventurer } from '../types/Adventurer';

export const SeasonBanner: React.FC = () => {
    const { user, isAuthenticated } = useAuth();
    const [adventurer, setAdventurer] = useState<Adventurer | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const loadProfile = async () => {
            if (isAuthenticated && user?.username) {
                setLoading(true);
                try {
                    // Assuming local username matches GitHub username for now
                    const data = await fetchAdventurer(user.username);
                    setAdventurer(data);
                } catch {
                    console.log('Adventurer profile not found for user', user.username);
                } finally {
                    setLoading(false);
                }
            }
        };
        loadProfile();
    }, [isAuthenticated, user?.username]);

    return (
        <div className="bg-gradient-to-r from-indigo-900 to-purple-900 rounded-xl overflow-hidden shadow-xl mb-8 relative">
            {/* Background Pattern */}
            <div className="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiNmZmYiLz48L3N2Zz4=')]"></div>

            <div className="relative px-6 py-8 md:px-8 md:py-10 flex flex-col md:flex-row items-center justify-between gap-6 text-white">
                <div className="flex-1 text-center md:text-left">
                    <div className="inline-block bg-yellow-500 text-yellow-900 text-xs font-bold px-2 py-0.5 rounded mb-2 uppercase tracking-wide">
                        Season 1 Active
                    </div>
                    <h2 className="text-3xl md:text-4xl font-extrabold mb-2 tracking-tight">
                        The Awakening
                    </h2>
                    <p className="text-indigo-200 text-lg max-w-xl">
                        The codebases of Web Hatchery are stirring. New features are needed, bugs must be squashed. Will you answer the call?
                    </p>

                    <div className="mt-6 flex flex-wrap gap-3 justify-center md:justify-start">
                        <Link to="/quests" className="px-5 py-2.5 bg-white text-indigo-900 font-bold rounded-lg hover:bg-gray-100 transition shadow-lg">
                            🔮 View Quests
                        </Link>
                        <Link to="/leaderboard" className="px-5 py-2.5 bg-indigo-700/50 text-white font-medium rounded-lg hover:bg-indigo-700 transition border border-indigo-500/50">
                            🏆 Leaderboard
                        </Link>
                        <Link to="/bosses" className="px-5 py-2.5 bg-red-600/80 text-white font-medium rounded-lg hover:bg-red-600 transition border border-red-500/50">
                            ⚔️ Boss Battle
                        </Link>
                    </div>
                </div>

                {/* User Stats / Login Prompt */}
                <div className="w-full md:w-auto bg-white/10 backdrop-blur-sm rounded-lg p-5 border border-white/20 min-w-[280px]">
                    {isAuthenticated && adventurer ? (
                        <div>
                            <div className="flex justify-between items-center mb-2">
                                <Link to={`/adventurers/${adventurer.github_username}`} className="font-bold text-lg hover:text-yellow-300 transition">
                                    {adventurer.github_username}
                                </Link>
                                <span className="text-xs bg-indigo-800 px-2 py-1 rounded text-indigo-200 uppercase font-semibold">
                                    Lv {adventurer.level}
                                </span>
                            </div>

                            <div className="text-sm text-indigo-200 mb-1 capitalize">
                                {adventurer.class.replace(/-/g, ' ')}
                            </div>

                            <div className="w-full bg-indigo-900/50 rounded-full h-2.5 mb-1">
                                <div
                                    className="bg-yellow-400 h-2.5 rounded-full"
                                    style={{ width: `${Math.min(100, (adventurer.xp_total % (adventurer.level * 150)) / (adventurer.level * 1.5))}%` }}
                                ></div>
                            </div>
                            <div className="text-right text-xs text-indigo-300 font-mono">
                                {adventurer.xp_total.toLocaleString()} XP
                            </div>
                        </div>
                    ) : (
                        <div className="text-center py-2">
                            <p className="font-bold mb-1">Not an Adventurer?</p>
                            <p className="text-sm text-indigo-200 mb-3">Login to track your progress!</p>
                            {!isAuthenticated && (
                                <Link to="/login" className="block w-full py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm font-bold transition">
                                    Login / Register
                                </Link>
                            )}
                            {isAuthenticated && !adventurer && !loading && (
                                <p className="text-xs text-yellow-200 mt-2">
                                    Tip: Set your username to match GitHub to sync!
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};
