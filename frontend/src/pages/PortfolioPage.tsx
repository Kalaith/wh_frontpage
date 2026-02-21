import React, { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { fetchAdventurer } from '../api/adventurerApi';
import { Adventurer } from '../types/Adventurer';

const PortfolioPage: React.FC = () => {
    const { username } = useParams<{ username: string }>();
    const [adventurer, setAdventurer] = useState<Adventurer | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (username) {
            fetchAdventurer(username)
                .then(setAdventurer)
                .catch(() => setAdventurer(null))
                .finally(() => setLoading(false));
        }
    }, [username]);

    if (loading) {
        return (
            <div className="flex items-center justify-center min-h-[60vh]">
                <div className="animate-spin rounded-full h-12 w-12 border-4 border-indigo-600 border-t-transparent"></div>
            </div>
        );
    }

    if (!adventurer) {
        return (
            <div className="max-w-3xl mx-auto px-4 py-16 text-center">
                <div className="text-5xl mb-4">🔍</div>
                <h1 className="text-2xl font-bold text-gray-900 mb-2">Adventurer Not Found</h1>
                <p className="text-gray-600">No contribution certificate available for this user.</p>
            </div>
        );
    }

    const levelPercent = Math.min(100, (adventurer.xp_total % (adventurer.level * 150)) / (adventurer.level * 1.5));

    return (
        <div className="max-w-3xl mx-auto px-4 py-8">
            {/* Certificate Card */}
            <div className="bg-gradient-to-br from-slate-50 to-indigo-50 border-2 border-indigo-200 rounded-2xl shadow-xl overflow-hidden">
                {/* Decorative Header */}
                <div className="bg-slate-50 border-b border-gray-200 px-8 py-6 text-center text-slate-900">
                    <div className="text-xs uppercase tracking-[0.3em] text-slate-500 mb-1 font-semibold">Web Hatchery</div>
                    <h1 className="text-3xl font-bold tracking-tight">Contribution Certificate</h1>
                    <div className="text-sm text-indigo-600 mt-1 font-medium">Season 1: The Awakening</div>
                </div>

                {/* Body */}
                <div className="px-8 py-8">
                    {/* Identity */}
                    <div className="text-center mb-8">
                        <div className="text-5xl mb-3">🧙</div>
                        <h2 className="text-2xl font-extrabold text-gray-900">{adventurer.github_username}</h2>
                        <div className="text-lg text-indigo-600 font-semibold capitalize mt-1">
                            {adventurer.equipped_title ?? adventurer.class.replace(/-/g, ' ')}
                        </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-3 gap-4 mb-8">
                        <div className="text-center bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <div className="text-3xl font-extrabold text-indigo-600">{adventurer.level}</div>
                            <div className="text-xs text-gray-500 uppercase font-semibold mt-1">Level</div>
                        </div>
                        <div className="text-center bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <div className="text-3xl font-extrabold text-purple-600">{adventurer.xp_total.toLocaleString()}</div>
                            <div className="text-xs text-gray-500 uppercase font-semibold mt-1">Total XP</div>
                        </div>
                        <div className="text-center bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                            <div className="text-3xl font-extrabold text-yellow-600">{adventurer.badges?.length ?? 0}</div>
                            <div className="text-xs text-gray-500 uppercase font-semibold mt-1">Badges</div>
                        </div>
                    </div>

                    {/* XP Progress */}
                    <div className="mb-8">
                        <div className="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Progress to Level {adventurer.level + 1}</span>
                            <span className="font-mono">{adventurer.xp_total.toLocaleString()} XP</span>
                        </div>
                        <div className="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div
                                className="bg-gradient-to-r from-indigo-500 to-purple-500 h-3 rounded-full transition-all duration-1000"
                                style={{ width: `${levelPercent}%` }}
                            ></div>
                        </div>
                    </div>

                    {/* Badges */}
                    {adventurer.badges && adventurer.badges.length > 0 && (
                        <div className="mb-8">
                            <h3 className="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">Earned Badges</h3>
                            <div className="flex flex-wrap gap-2">
                                {adventurer.badges.map((badge, i) => (
                                    <span key={i} className="bg-yellow-100 text-yellow-800 text-sm font-semibold px-3 py-1 rounded-full border border-yellow-200">
                                        🏅 {badge.badge_name}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Mastery */}
                    {adventurer.mastery && adventurer.mastery.length > 0 && (
                        <div className="mb-8">
                            <h3 className="text-sm font-bold text-gray-700 uppercase tracking-wide mb-3">Habitat Mastery</h3>
                            <div className="space-y-2">
                                {adventurer.mastery.map((m, i) => (
                                    <div key={i} className="flex justify-between items-center bg-white rounded-lg p-3 shadow-sm border border-gray-100">
                                        <span className="font-medium text-gray-900">{m.project_title || 'Project'}</span>
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs text-gray-500">{m.contributions} contributions</span>
                                            <span className="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-0.5 rounded">Lv {m.mastery_level}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Footer */}
                    <div className="text-center pt-4 border-t border-gray-200">
                        <p className="text-xs text-gray-400">
                            Generated by Web Hatchery Gamification Engine • {new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                        </p>
                        <div className="mt-3">
                            <Link to={`/adventurers/${adventurer.github_username}`} className="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                View Full Profile →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            {/* Share Buttons */}
            <div className="mt-6 text-center">
                <button
                    onClick={() => window.print()}
                    className="px-6 py-2.5 bg-gray-800 text-white font-semibold rounded-lg hover:bg-gray-900 transition shadow-md"
                >
                    🖨️ Print Certificate
                </button>
            </div>
        </div>
    );
};

export default PortfolioPage;
