import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../stores/authStore';
import { useToastStore } from '../stores/toastStore';
import { fetchAdventurer } from '../api/adventurerApi';
import { Adventurer } from '../types/Adventurer';

export const XPWidget: React.FC = () => {
    const { user, isAuthenticated } = useAuth();
    const { success } = useToastStore();
    const [adventurer, setAdventurer] = useState<Adventurer | null>(null);

    useEffect(() => {
        const loadProfile = async () => {
            if (isAuthenticated && user?.username) {
                try {
                    const data = await fetchAdventurer(user.username);
                    setAdventurer(data);

                    // Check for level up.
                    const storedLevel = localStorage.getItem(`adv_level_${user.username}`);
                    if (storedLevel) {
                        const oldLevel = parseInt(storedLevel, 10);
                        if (data.level > oldLevel) {
                            success(`Level up! You reached Level ${data.level}.`);
                        }
                    }
                    localStorage.setItem(`adv_level_${user.username}`, data.level.toString());
                } catch {
                    // Silent fail
                }
            }
        };

        loadProfile();
    }, [isAuthenticated, user?.username, success]);

    if (!isAuthenticated || !adventurer) return null;

    const nextLevelXP = adventurer.level * 100 * 1.5;
    const progressPercent = Math.min(100, (adventurer.xp_total % nextLevelXP) / nextLevelXP * 100);

    return (
        <Link
            to={`/adventurers/${adventurer.github_username}`}
            className="flex items-center gap-2 bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded-full border border-slate-700 hover:border-slate-600 transition group"
            title={`${adventurer.xp_total.toLocaleString()} Total XP`}
        >
            <span className="text-xs font-bold bg-indigo-500 text-white px-1.5 py-0.5 rounded flex items-center justify-center min-w-[24px] shadow-sm">
                {adventurer.level}
            </span>
            <div className="flex flex-col w-20">
                <div className="text-[10px] font-bold text-indigo-300 leading-none mb-0.5 truncate max-w-[80px]">
                    {adventurer.class.replace(/-/g, ' ')}
                </div>
                <div className="w-full bg-slate-900 rounded-full h-1.5 border border-slate-700/50">
                    <div
                        className="bg-gradient-to-r from-indigo-500 to-purple-500 h-1.5 rounded-full"
                        style={{ width: `${progressPercent}%` }}
                    ></div>
                </div>
            </div>
        </Link>
    );
};
