import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../stores/authStore';
import { useToastStore } from '../stores/toastStore';
import { fetchAdventurer } from '../api/adventurerApi';
import { Adventurer } from '../types/Adventurer';

export const XPWidget: React.FC = () => {
    const { user, isAuthenticated } = useAuth();
    const { addToast } = useToastStore();
    const [adventurer, setAdventurer] = useState<Adventurer | null>(null);

    useEffect(() => {
        const loadProfile = async () => {
            if (isAuthenticated && user?.username) {
                try {
                    const data = await fetchAdventurer(user.username);
                    setAdventurer(data);

                    // Check for Level Up
                    const storedLevel = localStorage.getItem(`adv_level_${user.username}`);
                    if (storedLevel) {
                        const oldLevel = parseInt(storedLevel, 10);
                        if (data.level > oldLevel) {
                            addToast({
                                type: 'success',
                                title: '🎉 LEVEL UP!',
                                message: `Congratulations! You reached Level ${data.level}!`
                            });
                        }
                    }
                    localStorage.setItem(`adv_level_${user.username}`, data.level.toString());

                } catch (err) {
                    // Silent fail
                }
            }
        };
        loadProfile();
    }, [isAuthenticated, user?.username, addToast]);

    if (!isAuthenticated || !adventurer) return null;

    const nextLevelXP = adventurer.level * 100 * 1.5;
    const progressPercent = Math.min(100, (adventurer.xp_total % nextLevelXP) / nextLevelXP * 100);

    return (
        <Link
            to={`/adventurers/${adventurer.github_username}`}
            className="flex items-center gap-2 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-full border border-indigo-100 transition group"
            title={`${adventurer.xp_total.toLocaleString()} Total XP`}
        >
            <span className="text-xs font-bold bg-indigo-600 text-white px-1.5 py-0.5 rounded flex items-center justify-center min-w-[24px]">
                {adventurer.level}
            </span>
            <div className="flex flex-col w-20">
                <div className="text-[10px] font-bold text-indigo-900 leading-none mb-0.5 truncate max-w-[80px]">
                    {adventurer.class.replace(/-/g, ' ')}
                </div>
                <div className="w-full bg-indigo-200 rounded-full h-1.5">
                    <div
                        className="bg-indigo-500 h-1.5 rounded-full"
                        style={{ width: `${progressPercent}%` }}
                    ></div>
                </div>
            </div>
        </Link>
    );
};
