import React, { useEffect, useState } from 'react';
import { fetchCurrentBoss } from '../api/bossApi';
import { Boss } from '../types/Boss';

const BossBattlePage: React.FC = () => {
    const [boss, setBoss] = useState<Boss | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const load = async () => {
            try {
                const data = await fetchCurrentBoss();
                setBoss(data);
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load boss');
            } finally {
                setLoading(false);
            }
        };
        load();
    }, []);

    if (loading) {
        return <div className="flex justify-center py-12"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div></div>;
    }

    if (error || !boss) {
        return (
            <div className="container mx-auto px-4 py-8 text-center">
                <p className="text-red-600 font-bold">{error || 'No active boss found'}</p>
            </div>
        );
    }

    const hpPercent = Math.max(0, (boss.hp_current / boss.hp_total) * 100);

    return (
        <div className="container mx-auto px-4 py-8">
            <header className="mb-12 text-center">
                <h1 className="text-5xl font-black text-red-900 mb-4 tracking-tight uppercase drop-shadow-sm">
                    ⚠️ BOSS BATTLE ACTIVE ⚠️
                </h1>
                <p className="text-xl text-red-700 font-medium">The realm is under attack. Adventurers, assemble!</p>
            </header>

            <div className="max-w-4xl mx-auto bg-white rounded-xl shadow-2xl overflow-hidden border-2 border-red-100">
                <div className="bg-red-900 text-white p-6 md:p-8 flex justify-between items-start">
                    <div>
                        <h2 className="text-3xl font-bold mb-2">{boss.name}</h2>
                        <span className="bg-red-800 text-red-100 text-xs px-2 py-1 rounded uppercase font-semibold tracking-wider border border-red-700">
                            {boss.status} threat
                        </span>
                    </div>
                    <div className="text-right">
                        <div className="text-3xl font-mono font-bold tracking-widest">{boss.hp_current.toLocaleString()} HP</div>
                        <div className="text-red-200 text-sm">/ {boss.hp_total.toLocaleString()} Max HP</div>
                    </div>
                </div>

                {/* HP Bar */}
                <div className="bg-gray-900 h-8 w-full relative">
                    <div
                        className="h-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-1000 ease-out relative overflow-hidden"
                        style={{ width: `${hpPercent}%` }}
                    >
                        <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTAgNDBMNDAgMEgwTDQwIDQwIiBzdHJva2U9InJnYmEoMjU1LDI1NSwyNTUsMC4xKSIgZmlsbD0ibm9uZSIvPjwvc3ZnPg==')] opacity-30"></div>
                    </div>
                </div>

                <div className="p-8">
                    <div className="prose prose-red max-w-none mb-8">
                        <p className="text-lg leading-relaxed text-gray-700">{boss.description}</p>
                    </div>

                    <div className="bg-red-50 border border-red-100 rounded-lg p-6 text-center">
                        <h3 className="text-xl font-bold text-red-900 mb-2">How to Fight</h3>
                        <p className="text-gray-600 mb-6">Complete quests labeled <code className="bg-red-100 text-red-800 px-1 py-0.5 rounded text-sm font-mono">boss:damage</code> to deal massive damage to this foe!</p>

                        <a
                            href="https://github.com/Kalaith/wh_frontpage/issues?q=is%3Aissue+is%3Aopen+label%3Aboss%3Adamage"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-bold rounded-md text-white bg-red-600 hover:bg-red-700 shadow-lg transform hover:-translate-y-0.5 transition-all"
                        >
                            ⚔️ Attack via GitHub Issues
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default BossBattlePage;
