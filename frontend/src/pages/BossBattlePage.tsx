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

    const hpPercent = boss.hp_total > 0 ? Math.max(0, (boss.hp_current / boss.hp_total) * 100) : 0;

    return (
        <div className="container mx-auto px-4 py-8">
            <header className="mb-10 text-center">
                <h1 className="text-5xl font-black text-red-900 mb-4 tracking-tight uppercase">Boss Battle Active</h1>
                <p className="text-lg text-red-700">Defeat risk through measurable checkpoints.</p>
            </header>

            <div className="max-w-5xl mx-auto bg-white rounded-xl shadow-2xl overflow-hidden border-2 border-red-100">
                <div className="bg-red-900 text-white p-6 md:p-8 flex justify-between items-start gap-4">
                    <div>
                        <h2 className="text-3xl font-bold mb-2">{boss.name}</h2>
                        <div className="flex flex-wrap gap-2">
                            <span className="bg-red-800 text-red-100 text-xs px-2 py-1 rounded uppercase font-semibold border border-red-700">
                                {boss.status}
                            </span>
                            <span className="bg-red-800 text-red-100 text-xs px-2 py-1 rounded border border-red-700">
                                Threat {boss.threat_level}
                            </span>
                            {boss.threat_type && (
                                <span className="bg-red-800 text-red-100 text-xs px-2 py-1 rounded border border-red-700">
                                    {boss.threat_type}
                                </span>
                            )}
                            {(boss.labels ?? []).map((label) => (
                                <span key={label} className="bg-red-700 text-red-100 text-xs px-2 py-1 rounded border border-red-600">
                                    {label}
                                </span>
                            ))}
                        </div>
                    </div>
                    <div className="text-right">
                        <div className="text-3xl font-mono font-bold tracking-wider">{boss.hp_current.toLocaleString()} HP</div>
                        <div className="text-red-200 text-sm">/ {boss.hp_total.toLocaleString()} Max HP</div>
                        {boss.deadline && <div className="text-red-200 text-xs mt-1">Deadline: {boss.deadline}</div>}
                    </div>
                </div>

                <div className="bg-gray-900 h-8 w-full relative">
                    <div
                        className="h-full bg-gradient-to-r from-red-600 to-red-500 transition-all duration-1000 ease-out"
                        style={{ width: `${hpPercent}%` }}
                    ></div>
                </div>

                <div className="p-8 space-y-6">
                    {boss.description && <p className="text-lg leading-relaxed text-gray-700">{boss.description}</p>}

                    <div className="grid md:grid-cols-2 gap-4">
                        {boss.risk_level && (
                            <div className="bg-rose-50 border border-rose-200 rounded-lg p-4">
                                <h3 className="text-sm font-semibold text-rose-900 mb-1">Risk Level</h3>
                                <p className="text-rose-700 text-sm">{boss.risk_level}</p>
                            </div>
                        )}
                        {boss.rollback_plan && (
                            <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <h3 className="text-sm font-semibold text-amber-900 mb-1">Rollback Plan</h3>
                                <p className="text-amber-800 text-sm">{boss.rollback_plan}</p>
                            </div>
                        )}
                    </div>

                    {(boss.kill_criteria?.length ?? 0) > 0 && (
                        <section className="bg-white border border-indigo-200 rounded-lg p-4">
                            <h3 className="font-bold text-indigo-900 mb-2">Kill Criteria</h3>
                            <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                {boss.kill_criteria?.map((criterion, idx) => (
                                    <li key={idx}>{criterion}</li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {(boss.hp_tasks?.length ?? 0) > 0 && (
                        <section className="bg-white border border-red-200 rounded-lg p-4">
                            <h3 className="font-bold text-red-900 mb-2">HP Tasks</h3>
                            <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                {boss.hp_tasks?.map((task, idx) => (
                                    <li key={idx}>{task}</li>
                                ))}
                            </ul>
                        </section>
                    )}

                    {(boss.proof_required?.length ?? 0) > 0 && (
                        <section>
                            <h3 className="text-sm font-semibold text-gray-700 mb-2">Proof Required</h3>
                            <div className="flex flex-wrap gap-2">
                                {boss.proof_required?.map((proof) => (
                                    <span key={proof} className="text-xs px-2 py-1 rounded bg-purple-100 text-purple-700">
                                        {proof}
                                    </span>
                                ))}
                            </div>
                        </section>
                    )}

                    {boss.github_issue_url && (
                        <a
                            href={boss.github_issue_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center justify-center px-6 py-3 text-sm font-bold rounded-md text-white bg-red-600 hover:bg-red-700 transition"
                        >
                            Open Boss Source
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
};

export default BossBattlePage;
