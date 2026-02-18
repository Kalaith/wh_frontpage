import React, { useEffect, useMemo, useState } from 'react';
import { fetchQuests, fetchMyQuests, acceptQuest } from '../api/questApi';
import { Quest, QuestAcceptance, RankProgress } from '../types/Quest';
import { QuestCard } from '../components/QuestCard';
import { RuneSagePromptBuilder } from '../components/RuneSagePromptBuilder';
import { RankProgressBar } from '../components/RankProgressBar';
import api from '../api/api';
import { useAuth } from '../stores/authStore';
import { Project } from '../types/projects';

interface DependencyStatus {
    blocked: boolean;
    unresolved: string[];
    reason: string | null;
}

const QuestBoardPage: React.FC = () => {
    const [quests, setQuests] = useState<Quest[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedClass, setSelectedClass] = useState<string>('');
    const [selectedDifficulty, setSelectedDifficulty] = useState<number>(0);
    const [selectedQuest, setSelectedQuest] = useState<Quest | null>(null);

    // Quest acceptance state
    const [myAcceptances, setMyAcceptances] = useState<QuestAcceptance[]>([]);
    const [rankProgress, setRankProgress] = useState<RankProgress | null>(null);
    const [accepting, setAccepting] = useState(false);

    const [projects, setProjects] = useState<Project[]>([]);
    const [showPostModal, setShowPostModal] = useState(false);
    const [postError, setPostError] = useState<string | null>(null);
    const [posting, setPosting] = useState(false);
    const [postForm, setPostForm] = useState({
        project_id: '',
        title: '',
        description: '',
        rank_required: 'Iron',
        quest_level: '1',
        dependency_type: 'Independent',
        depends_on: '',
        unlock_condition: 'n/a',
    });

    const { user, isAuthenticated } = useAuth();
    const normalizedRole = (user?.role ?? '').toLowerCase();
    const canPostQuests = isAuthenticated && (normalizedRole === 'admin' || normalizedRole === 'guild_master');

    const loadQuests = async () => {
        setLoading(true);
        setError(null);
        try {
            const filters: { class?: string; difficulty?: number } = {};
            if (selectedClass) filters.class = selectedClass;
            if (selectedDifficulty > 0) filters.difficulty = selectedDifficulty;

            const data = await fetchQuests(filters);
            setQuests(data);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to load quests');
        } finally {
            setLoading(false);
        }
    };

    const loadMyQuests = async () => {
        if (!isAuthenticated) {
            setMyAcceptances([]);
            setRankProgress(null);
            return;
        }
        try {
            const data = await fetchMyQuests();
            setMyAcceptances(data.acceptances || []);
            setRankProgress(data.rank_progress || null);
        } catch {
            // Silent fail — user might not have an adventurer profile
        }
    };

    useEffect(() => {
        loadQuests();
    }, [selectedClass, selectedDifficulty]);

    useEffect(() => {
        loadMyQuests();
    }, [isAuthenticated]);

    useEffect(() => {
        const loadProjects = async () => {
            if (!canPostQuests) {
                setProjects([]);
                return;
            }
            const res = await api.getProjects();
            if (res.success && res.data?.projects) {
                setProjects(res.data.projects);
            } else {
                setProjects([]);
            }
        };
        loadProjects();
    }, [canPostQuests]);

    const availableProjects = useMemo(() => {
        if (!canPostQuests) return [];
        if (normalizedRole === 'admin') return projects;
        return projects.filter((p) => (p.owner_user_id ?? null) === (user?.id ?? -1));
    }, [canPostQuests, normalizedRole, projects, user?.id]);

    // Build a map of quest_ref -> acceptance status
    const acceptanceMap = useMemo(() => {
        const map = new Map<string, QuestAcceptance>();
        myAcceptances.forEach((a) => map.set(a.quest_ref, a));
        return map;
    }, [myAcceptances]);

    // Derive the quest_ref for a quest
    const getQuestRef = (quest: Quest): string => {
        return quest.quest_code || `quest-${quest.id}`;
    };

    const getAcceptanceForQuest = (quest: Quest): QuestAcceptance | null => {
        return acceptanceMap.get(getQuestRef(quest)) ?? null;
    };

    const projectClasses = [
        { id: 'bug-hunter', label: 'Bug Hunter' },
        { id: 'patch-crafter', label: 'Patch Crafter' },
        { id: 'feature-smith', label: 'Feature Smith' },
        { id: 'doc-sage', label: 'Doc Sage' },
        { id: 'ux-alchemist', label: 'UX Alchemist' },
        { id: 'ops-ranger', label: 'Ops Ranger' },
        { id: 'test-summoner', label: 'Test Summoner' },
    ];

    const normalizeRef = (value: string): string => value.trim().toLowerCase();

    const questRefMap = useMemo(() => {
        const map = new Map<string, Quest>();
        quests.forEach((quest) => {
            map.set(normalizeRef(String(quest.id)), quest);
            map.set(normalizeRef(String(quest.number)), quest);
            if (quest.quest_code) map.set(normalizeRef(quest.quest_code), quest);
            map.set(normalizeRef(quest.title), quest);
        });
        return map;
    }, [quests]);

    const dependencyStatusByQuestId = useMemo(() => {
        const statusMap = new Map<number, DependencyStatus>();

        quests.forEach((quest) => {
            const dependencyType = (quest.dependency_type ?? 'Independent').toLowerCase();
            const dependsOnRefs = quest.depends_on ?? [];

            const unresolved = dependsOnRefs
                .map((ref) => questRefMap.get(normalizeRef(ref)))
                .filter((q): q is Quest => Boolean(q))
                .filter((q) => q.id !== quest.id)
                .map((q) => q.title);

            let blocked = false;
            let reason: string | null = null;

            if (dependencyType === 'blocked') {
                blocked = true;
                reason = quest.unlock_condition ?? (unresolved.length > 0 ? `Requires: ${unresolved.join(', ')}` : 'Blocked by dependency rules.');
            } else if (dependencyType === 'chained' && unresolved.length > 0) {
                blocked = true;
                reason = `Complete first: ${unresolved.join(', ')}`;
            }

            statusMap.set(quest.id, { blocked, unresolved, reason });
        });

        return statusMap;
    }, [questRefMap, quests]);

    const getDependencyStatus = (quest: Quest): DependencyStatus => (
        dependencyStatusByQuestId.get(quest.id) ?? { blocked: false, unresolved: [], reason: null }
    );

    const handleAcceptQuest = async (quest: Quest) => {
        const status = getDependencyStatus(quest);
        if (status.blocked) return;

        if (!isAuthenticated) {
            if (quest.url && quest.url !== '#') {
                window.open(quest.url, '_blank', 'noopener,noreferrer');
            }
            return;
        }

        const questRef = getQuestRef(quest);
        const existing = getAcceptanceForQuest(quest);
        if (existing) {
            // Already accepted — open the quest URL for existing flows
            if (quest.url && quest.url !== '#') {
                window.open(quest.url, '_blank', 'noopener,noreferrer');
            }
            return;
        }

        setAccepting(true);
        try {
            await acceptQuest(questRef, quest.rank_required);
            await loadMyQuests();
        } catch (err) {
            console.error('Failed to accept quest:', err);
        } finally {
            setAccepting(false);
        }
    };

    const handlePostQuest = async () => {
        setPostError(null);
        if (!postForm.project_id || !postForm.title.trim() || !postForm.description.trim()) {
            setPostError('Project, title, and description are required.');
            return;
        }

        setPosting(true);
        try {
            const dependsOn = postForm.depends_on
                .split(',')
                .map((s) => s.trim())
                .filter(Boolean);

            const res = await api.createProjectQuest(Number(postForm.project_id), {
                title: postForm.title.trim(),
                description: postForm.description.trim(),
                rank_required: postForm.rank_required,
                quest_level: Number(postForm.quest_level),
                dependency_type: postForm.dependency_type,
                depends_on: dependsOn,
                unlock_condition: postForm.unlock_condition.trim() || 'n/a',
            });

            if (!res.success) {
                setPostError(
                    typeof res.error === 'string'
                        ? res.error
                        : res.error?.message ?? 'Failed to post quest.'
                );
                return;
            }

            setShowPostModal(false);
            setPostForm({
                project_id: '',
                title: '',
                description: '',
                rank_required: 'Iron',
                quest_level: '1',
                dependency_type: 'Independent',
                depends_on: '',
                unlock_condition: 'n/a',
            });
            await loadQuests();
        } catch (e) {
            setPostError(e instanceof Error ? e.message : 'Failed to post quest.');
        } finally {
            setPosting(false);
        }
    };

    const selectedQuestStatus = selectedQuest ? getDependencyStatus(selectedQuest) : null;
    const selectedQuestAcceptance = selectedQuest ? getAcceptanceForQuest(selectedQuest) : null;

    const getModalActionButton = () => {
        if (!selectedQuest || !selectedQuestStatus) return null;

        const acceptance = selectedQuestAcceptance;

        if (selectedQuestStatus.blocked) {
            return (
                <button
                    type="button"
                    disabled
                    className="px-4 py-2 rounded font-semibold bg-gray-300 text-gray-600 cursor-not-allowed"
                >
                    Locked by Dependencies
                </button>
            );
        }

        if (!acceptance) {
            return (
                <button
                    type="button"
                    onClick={() => handleAcceptQuest(selectedQuest)}
                    disabled={accepting}
                    className={`px-4 py-2 rounded font-semibold ${accepting ? 'bg-gray-400 text-white' : 'bg-indigo-600 text-white hover:bg-indigo-700'
                        }`}
                >
                    {accepting ? 'Accepting...' : '⚔️ Accept Quest'}
                </button>
            );
        }

        switch (acceptance.status) {
            case 'accepted':
                return (
                    <button
                        type="button"
                        disabled
                        className="px-4 py-2 rounded font-semibold bg-amber-100 text-amber-700 border border-amber-200 cursor-not-allowed"
                        title="Submission flow coming soon (Step 2)"
                    >
                        📦 Submit Proof (Coming Soon)
                    </button>
                );
            case 'submitted':
                return (
                    <button
                        type="button"
                        disabled
                        className="px-4 py-2 rounded font-semibold bg-blue-100 text-blue-700 border border-blue-200 cursor-not-allowed"
                    >
                        ⏳ Awaiting Review
                    </button>
                );
            case 'completed':
                return (
                    <button
                        type="button"
                        disabled
                        className="px-4 py-2 rounded font-semibold bg-emerald-100 text-emerald-700 border border-emerald-200 cursor-not-allowed"
                    >
                        ✅ Completed
                    </button>
                );
            case 'rejected':
                return (
                    <button
                        type="button"
                        onClick={() => handleAcceptQuest(selectedQuest)}
                        className="px-4 py-2 rounded font-semibold bg-rose-600 text-white hover:bg-rose-700"
                    >
                        🔄 Re-accept Quest
                    </button>
                );
            default:
                return null;
        }
    };

    return (
        <div className="container mx-auto px-4 py-8">
            <header className="mb-8 text-center">
                <h1 className="text-4xl font-extrabold text-gray-900 mb-2">
                    <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
                        Quest Board
                    </span>
                </h1>
                <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                    Choose your path, adventurer. Chained quests unlock as prerequisites are cleared.
                </p>
                {canPostQuests && (
                    <button
                        type="button"
                        onClick={() => setShowPostModal(true)}
                        className="mt-4 px-4 py-2 rounded bg-indigo-600 text-white font-semibold hover:bg-indigo-700"
                    >
                        Post Quest
                    </button>
                )}
            </header>

            {/* Rank Progress Bar */}
            {isAuthenticated && <RankProgressBar rankProgress={rankProgress} />}

            <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-8 flex flex-wrap gap-4 items-center justify-center">
                <div className="flex items-center gap-2">
                    <label className="font-semibold text-gray-700">Class:</label>
                    <select
                        value={selectedClass}
                        onChange={(e) => setSelectedClass(e.target.value)}
                        className="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">All Classes</option>
                        {projectClasses.map((c) => (
                            <option key={c.id} value={c.id}>{c.label}</option>
                        ))}
                    </select>
                </div>

                <div className="flex items-center gap-2">
                    <label className="font-semibold text-gray-700">Difficulty:</label>
                    <select
                        value={selectedDifficulty}
                        onChange={(e) => setSelectedDifficulty(Number(e.target.value))}
                        className="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="0">All Difficulties</option>
                        <option value="1">Level 1</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3</option>
                        <option value="4">Level 4</option>
                        <option value="5">Level 5</option>
                    </select>
                </div>
            </div>

            {loading ? (
                <div className="flex justify-center py-12">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                </div>
            ) : error ? (
                <div className="text-center py-12 bg-red-50 rounded-lg border border-red-200 text-red-700">
                    <p className="font-bold">Error loading quests:</p>
                    <p>{error}</p>
                    <button
                        onClick={loadQuests}
                        className="mt-4 px-4 py-2 bg-white border border-red-300 rounded hover:bg-red-50"
                    >
                        Try Again
                    </button>
                </div>
            ) : quests.length === 0 ? (
                <div className="text-center py-12 bg-gray-50 rounded-lg border border-gray-200 text-gray-500">
                    <p className="text-xl font-medium mb-2">No active quests found.</p>
                    <p>Check back later or try adjusting your filters.</p>
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    {quests.map((quest) => (
                        <QuestCard
                            key={quest.id}
                            quest={quest}
                            isBlocked={getDependencyStatus(quest).blocked}
                            onViewDetails={setSelectedQuest}
                            acceptance={getAcceptanceForQuest(quest)}
                        />
                    ))}
                </div>
            )}

            {/* Post Quest Modal */}
            {showPostModal && (
                <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
                    <div className="bg-white w-full max-w-xl rounded-xl shadow-2xl border border-gray-200">
                        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <h2 className="text-lg font-bold text-gray-900">Post New Quest</h2>
                            <button type="button" onClick={() => setShowPostModal(false)} className="text-gray-500 hover:text-gray-700">Close</button>
                        </div>
                        <div className="p-6 space-y-3">
                            {postError && <p className="text-sm text-red-600">{postError}</p>}
                            <select
                                value={postForm.project_id}
                                onChange={(e) => setPostForm((prev) => ({ ...prev, project_id: e.target.value }))}
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            >
                                <option value="">Select project</option>
                                {availableProjects.map((project) => (
                                    <option key={project.id} value={project.id}>{project.title}</option>
                                ))}
                            </select>
                            <input
                                value={postForm.title}
                                onChange={(e) => setPostForm((prev) => ({ ...prev, title: e.target.value }))}
                                placeholder="Quest title"
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                            <textarea
                                value={postForm.description}
                                onChange={(e) => setPostForm((prev) => ({ ...prev, description: e.target.value }))}
                                placeholder="Quest description"
                                className="w-full border border-gray-300 rounded px-3 py-2 min-h-24"
                            />
                            <div className="grid grid-cols-2 gap-3">
                                <select
                                    value={postForm.rank_required}
                                    onChange={(e) => setPostForm((prev) => ({ ...prev, rank_required: e.target.value }))}
                                    className="border border-gray-300 rounded px-3 py-2"
                                >
                                    <option>Iron</option>
                                    <option>Silver</option>
                                    <option>Gold</option>
                                    <option>Jade</option>
                                    <option>Diamond</option>
                                </select>
                                <select
                                    value={postForm.quest_level}
                                    onChange={(e) => setPostForm((prev) => ({ ...prev, quest_level: e.target.value }))}
                                    className="border border-gray-300 rounded px-3 py-2"
                                >
                                    <option value="1">Level 1</option>
                                    <option value="2">Level 2</option>
                                    <option value="3">Level 3</option>
                                    <option value="4">Level 4</option>
                                    <option value="5">Level 5</option>
                                </select>
                            </div>
                            <select
                                value={postForm.dependency_type}
                                onChange={(e) => setPostForm((prev) => ({ ...prev, dependency_type: e.target.value }))}
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            >
                                <option>Independent</option>
                                <option>Chained</option>
                                <option>Blocked</option>
                            </select>
                            <input
                                value={postForm.depends_on}
                                onChange={(e) => setPostForm((prev) => ({ ...prev, depends_on: e.target.value }))}
                                placeholder="Depends on (comma separated, e.g. Q1,Q2)"
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                            <input
                                value={postForm.unlock_condition}
                                onChange={(e) => setPostForm((prev) => ({ ...prev, unlock_condition: e.target.value }))}
                                placeholder="Unlock condition"
                                className="w-full border border-gray-300 rounded px-3 py-2"
                            />
                        </div>
                        <div className="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                            <button type="button" onClick={() => setShowPostModal(false)} className="px-4 py-2 border border-gray-300 rounded">Cancel</button>
                            <button
                                type="button"
                                disabled={posting}
                                onClick={handlePostQuest}
                                className={`px-4 py-2 rounded text-white font-semibold ${posting ? 'bg-gray-400' : 'bg-indigo-600 hover:bg-indigo-700'}`}
                            >
                                {posting ? 'Posting...' : 'Post Quest'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Quest Details Modal */}
            {selectedQuest && selectedQuestStatus && (
                <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
                    <div className="bg-white w-full max-w-3xl rounded-xl shadow-2xl border border-gray-200 max-h-[90vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-center justify-between">
                            <div>
                                <h2 className="text-xl font-bold text-gray-900">{selectedQuest.title}</h2>
                                <p className="text-sm text-gray-500">{selectedQuest.quest_code ?? `Quest #${selectedQuest.number}`}</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setSelectedQuest(null)}
                                className="text-gray-500 hover:text-gray-700 text-sm font-medium"
                            >
                                Close
                            </button>
                        </div>

                        <div className="p-6 space-y-5">
                            <div className="flex flex-wrap gap-2">
                                {selectedQuest.rank_required && (
                                    <span className="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-700">
                                        Rank Required: {selectedQuest.rank_required}
                                    </span>
                                )}
                                {(selectedQuest.quest_level || selectedQuest.difficulty) && (
                                    <span className="text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-700">
                                        Quest Level: {selectedQuest.quest_level ?? selectedQuest.difficulty}
                                    </span>
                                )}
                                {selectedQuest.dependency_type && (
                                    <span className="text-xs px-2 py-1 rounded bg-slate-100 text-slate-700">
                                        Dependency: {selectedQuest.dependency_type}
                                    </span>
                                )}
                                {selectedQuest.due_date && (
                                    <span className="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800">
                                        Due: {selectedQuest.due_date}
                                    </span>
                                )}
                                {selectedQuestAcceptance && (
                                    <span className={`text-xs px-2 py-1 rounded font-semibold ${selectedQuestAcceptance.status === 'completed' ? 'bg-emerald-100 text-emerald-700'
                                            : selectedQuestAcceptance.status === 'submitted' ? 'bg-blue-100 text-blue-700'
                                                : selectedQuestAcceptance.status === 'accepted' ? 'bg-amber-100 text-amber-700'
                                                    : 'bg-red-100 text-red-700'
                                        }`}>
                                        Status: {selectedQuestAcceptance.status.charAt(0).toUpperCase() + selectedQuestAcceptance.status.slice(1)}
                                    </span>
                                )}
                            </div>

                            {selectedQuestStatus.blocked && (
                                <div className="border border-rose-200 bg-rose-50 text-rose-800 rounded-lg p-3 text-sm">
                                    <strong>Locked:</strong> {selectedQuestStatus.reason ?? 'Complete required quests first.'}
                                </div>
                            )}

                            {(selectedQuest.depends_on?.length ?? 0) > 0 && (
                                <section>
                                    <h3 className="text-sm font-semibold text-gray-800 mb-1">Depends On</h3>
                                    <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                        {selectedQuest.depends_on?.map((depRef, index) => {
                                            const depQuest = questRefMap.get(normalizeRef(depRef));
                                            const label = depQuest ? depQuest.title : depRef;
                                            const unresolved = selectedQuestStatus.unresolved.includes(label);
                                            return (
                                                <li key={`${selectedQuest.id}-dep-${index}`}>
                                                    {label}
                                                    {' '}
                                                    <span className={`text-xs ${unresolved ? 'text-rose-600' : 'text-emerald-600'}`}>
                                                        {unresolved ? '(Pending)' : '(Ready/Cleared)'}
                                                    </span>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                    {selectedQuest.unlock_condition && (
                                        <p className="text-xs text-gray-600 mt-2">Unlock condition: {selectedQuest.unlock_condition}</p>
                                    )}
                                </section>
                            )}

                            {selectedQuest.body && (
                                <section>
                                    <h3 className="text-sm font-semibold text-gray-800 mb-1">Overview</h3>
                                    <p className="text-sm text-gray-700 leading-relaxed">{selectedQuest.body}</p>
                                </section>
                            )}

                            {selectedQuest.goal && (
                                <section>
                                    <h3 className="text-sm font-semibold text-gray-800 mb-1">Goal</h3>
                                    <p className="text-sm text-gray-700">{selectedQuest.goal}</p>
                                </section>
                            )}

                            {(selectedQuest.player_steps?.length ?? 0) > 0 && (
                                <section>
                                    <h3 className="text-sm font-semibold text-gray-800 mb-1">Steps</h3>
                                    <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                        {selectedQuest.player_steps?.map((step, index) => (
                                            <li key={`${selectedQuest.id}-step-${index}`}>{step}</li>
                                        ))}
                                    </ul>
                                </section>
                            )}

                            {(selectedQuest.done_when?.length ?? 0) > 0 && (
                                <section>
                                    <h3 className="text-sm font-semibold text-gray-800 mb-1">Done When</h3>
                                    <ul className="list-disc list-inside text-sm text-gray-700 space-y-1">
                                        {selectedQuest.done_when?.map((item, index) => (
                                            <li key={`${selectedQuest.id}-done-${index}`}>{item}</li>
                                        ))}
                                    </ul>
                                </section>
                            )}

                            {(selectedQuest.proof_required?.length ?? 0) > 0 && (
                                <section>
                                    <h3 className="text-sm font-semibold text-gray-800 mb-1">Proof Required</h3>
                                    <div className="flex flex-wrap gap-2">
                                        {selectedQuest.proof_required?.map((proof) => (
                                            <span key={proof} className="text-xs px-2 py-1 rounded bg-purple-100 text-purple-700">
                                                {proof}
                                            </span>
                                        ))}
                                    </div>
                                </section>
                            )}

                            {/* RuneSage Prompt Builder — replaces the old simple RS Brief display */}
                            {selectedQuest.rs_brief && (
                                <RuneSagePromptBuilder
                                    rsBrief={selectedQuest.rs_brief}
                                    questTitle={selectedQuest.title}
                                />
                            )}
                        </div>

                        <div className="border-t border-gray-100 px-6 py-4 flex items-center justify-end gap-3">
                            <button
                                type="button"
                                onClick={() => setSelectedQuest(null)}
                                className="px-4 py-2 rounded border border-gray-200 text-gray-700 hover:bg-gray-50"
                            >
                                Back
                            </button>
                            {getModalActionButton()}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default QuestBoardPage;
