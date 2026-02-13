import React, { useEffect, useState } from 'react';

interface WeeklyHeistData {
    goal: string;
    target: number;
    current: number;
    participants: number;
    reward: string;
    endsAt: string;
}

const WeeklyHeist: React.FC = () => {
    const [heist, setHeist] = useState<WeeklyHeistData | null>(null);

    useEffect(() => {
        // Mock data for now — would come from /api/heist/current  
        setHeist({
            goal: 'Merge 25 Pull Requests',
            target: 25,
            current: 14,
            participants: 8,
            reward: '500 XP Bonus + Epic Loot Crate for all participants',
            endsAt: getNextFriday(),
        });
    }, []);

    if (!heist) return null;

    const progress = Math.min(100, (heist.current / heist.target) * 100);
    const daysLeft = Math.max(0, Math.ceil((new Date(heist.endsAt).getTime() - Date.now()) / 86400000));

    return (
        <div className="bg-gradient-to-br from-emerald-800 to-teal-900 rounded-xl shadow-xl overflow-hidden text-white">
            <div className="px-6 py-5">
                <div className="flex items-center justify-between mb-4">
                    <div>
                        <div className="flex items-center gap-2 mb-1">
                            <span className="text-2xl">🏴‍☠️</span>
                            <h3 className="text-xl font-bold">Weekly Heist</h3>
                        </div>
                        <p className="text-emerald-200 text-sm">Team effort — everyone wins together!</p>
                    </div>
                    <div className="text-right">
                        <div className="text-xs text-emerald-300 uppercase font-semibold">Time Left</div>
                        <div className="text-2xl font-bold">{daysLeft}d</div>
                    </div>
                </div>

                {/* Goal */}
                <div className="bg-white/10 rounded-lg p-4 mb-4 backdrop-blur-sm">
                    <div className="text-sm text-emerald-200 mb-1">Mission Objective</div>
                    <div className="font-bold text-lg">{heist.goal}</div>
                </div>

                {/* Progress Bar */}
                <div className="mb-3">
                    <div className="flex justify-between text-sm mb-1">
                        <span className="text-emerald-200">Progress</span>
                        <span className="font-mono font-bold">{heist.current} / {heist.target}</span>
                    </div>
                    <div className="w-full bg-emerald-950/50 rounded-full h-4 overflow-hidden">
                        <div
                            className="bg-gradient-to-r from-yellow-400 to-amber-500 h-4 rounded-full transition-all duration-1000 ease-out relative"
                            style={{ width: `${progress}%` }}
                        >
                            <div className="absolute inset-0 bg-white/20 animate-pulse rounded-full"></div>
                        </div>
                    </div>
                </div>

                {/* Stats Row */}
                <div className="flex justify-between items-center text-sm">
                    <div className="flex items-center gap-1 text-emerald-200">
                        <span>👥</span>
                        <span>{heist.participants} participants</span>
                    </div>
                    <div className="flex items-center gap-1 text-yellow-300">
                        <span>🏆</span>
                        <span className="text-xs">{heist.reward}</span>
                    </div>
                </div>
            </div>
        </div>
    );
};

function getNextFriday(): string {
    const d = new Date();
    const day = d.getDay();
    const daysUntilFriday = (5 - day + 7) % 7 || 7;
    d.setDate(d.getDate() + daysUntilFriday);
    return d.toISOString();
}

export default WeeklyHeist;
