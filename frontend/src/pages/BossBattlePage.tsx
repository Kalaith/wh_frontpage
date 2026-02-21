import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { fetchCurrentBosses } from '../api/bossApi';
import { Boss } from '../types/Boss';

const BossBattlePage: React.FC = () => {
    const [bosses, setBosses] = useState<Boss[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const load = async () => {
            try {
                const data = await fetchCurrentBosses();
                setBosses(data);
            } catch (err) {
                setError(err instanceof Error ? err.message : 'Failed to load bosses');
            } finally {
                setLoading(false);
            }
        };

        load();
    }, []);

    if (loading) {
        return <div className="flex justify-center py-12"><div className="animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div></div>;
    }

    if (error || bosses.length === 0) {
        return (
            <div className="container mx-auto px-4 py-8 text-center">
                <p className="text-red-600 font-bold">{error ?? 'No active bosses found'}</p>
            </div>
        );
    }

    return (
        <div className="container mx-auto px-4 py-8">
            <header className="mb-10 text-center">
                <h1 className="text-4xl font-bold text-slate-900 mb-4 tracking-tight">Active Boss Battles</h1>
                <p className="text-lg text-slate-600">Raid the Backlog. Unite to crush critical tech debt and major features across the platform.</p>
            </header>

            <div className="space-y-12">
                {bosses.map((boss) => {
                    const hpPercent = boss.hp_total > 0 ? Math.max(0, (boss.hp_current / boss.hp_total) * 100) : 0;

                    return (
                        <div key={boss.id} className="max-w-5xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div className="bg-red-800 text-white p-6 md:p-8 flex justify-between items-start gap-4 relative">
                                {boss.project_name && (
                                    <div className="absolute top-0 right-0 bg-indigo-600 text-xs font-bold px-3 py-1 rounded-bl-lg shadow uppercase tracking-wider">
                                        Project: {boss.project_name}
                                    </div>
                                )}
                                <div>
                                    <h2 className="text-3xl font-bold mb-2">{boss.name}</h2>
                                    <div className="flex flex-wrap gap-2">
                                        <span className={`text-xs px-2 py-1 rounded uppercase font-semibold border ${boss.phase > 1 ? 'bg-purple-800 text-purple-100 border-purple-700' : 'bg-red-800 text-red-100 border-red-700'}`}>
                                            {boss.status} {boss.phase > 1 && `(Phase ${boss.phase})`}
                                        </span>
                                        <span className="bg-red-800 text-red-100 text-xs px-2 py-1 rounded border border-red-700">
                                            Threat {boss.threat_level}
                                        </span>
                                        {boss.threat_type && (
                                            <span className="bg-red-800 text-red-100 text-xs px-2 py-1 rounded border border-red-700">
                                                {boss.threat_type}
                                            </span>
                                        )}

                                        {boss.risk_level && (
                                            <span className="bg-amber-100 text-amber-800 text-xs px-2 py-1 rounded border border-amber-200 font-semibold shadow-sm">
                                                Risk Level: {boss.risk_level}
                                            </span>
                                        )}
                                        {(boss.labels ?? []).map((label) => {
                                            const displayLabel = label.replace(/^type:/i, '');
                                            const formattedLabel = displayLabel.charAt(0).toUpperCase() + displayLabel.slice(1);
                                            return (
                                                <span key={label} className="bg-red-700 text-red-100 text-xs px-2 py-1 rounded border border-red-600 shadow-sm">
                                                    {formattedLabel}
                                                </span>
                                            );
                                        })}
                                    </div>
                                </div>
                                <div className="text-right">
                                    <div className="text-3xl font-mono font-bold tracking-wider">{boss.hp_current.toLocaleString()} HP</div>
                                    <div className="text-red-200 text-sm">/ {boss.hp_total.toLocaleString()} Max HP</div>
                                    {boss.deadline && <div className="text-red-200 text-xs mt-1">Deadline: {boss.deadline}</div>}
                                </div>
                            </div>

                            <div className="bg-gray-100 h-8 w-full relative shadow-inner">
                                <div
                                    className={`h-full transition-all duration-1000 ease-out shadow-sm ${boss.phase > 1 ? 'bg-gradient-to-r from-purple-500 to-fuchsia-600' : 'bg-gradient-to-r from-red-500 to-rose-600'}`}
                                    style={{ width: `${hpPercent}%` }}
                                ></div>
                            </div>

                            <div className="p-8 space-y-6">
                                {boss.description && <p className="text-lg leading-relaxed text-gray-700">{boss.description}</p>}

                                <div className="grid md:grid-cols-2 gap-4">
                                    {boss.rollback_plan && (
                                        <div className="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                            <h3 className="text-sm font-semibold text-amber-900 mb-1">Retreat Strategy (Rollback)</h3>
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



                                <div className="flex flex-wrap gap-4 mt-6">
                                    {boss.github_issue_url && (
                                        <a
                                            href={boss.github_issue_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center px-6 py-3 text-sm font-bold rounded-md text-slate-700 bg-gray-100 hover:bg-gray-200 transition border border-gray-300"
                                        >
                                            Open Boss Source
                                        </a>
                                    )}

                                    {boss.project_id && (
                                        <Link
                                            to={`/quests?project_id=${boss.project_id}`}
                                            className="inline-flex items-center justify-center px-6 py-3 text-sm font-bold rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm"
                                        >
                                            ⚔️ View Project Quests
                                        </Link>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default BossBattlePage;
