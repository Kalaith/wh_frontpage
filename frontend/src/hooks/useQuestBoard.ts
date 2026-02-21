import { useEffect, useMemo, useState, useCallback } from 'react';
import { fetchQuests, fetchMyQuests, acceptQuest, submitQuest, cancelQuest } from '../api/questApi';
import { Quest, QuestAcceptance, RankProgress } from '../types/Quest';
import { useAuth } from '../stores/authStore';

export interface DependencyStatus {
    blocked: boolean;
    unresolved: string[];
    reason: string | null;
}

const RANK_ORDER = ['iron', 'silver', 'gold', 'jade', 'diamond'];

export const useQuestBoard = () => {
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
    const [submitting, setSubmitting] = useState(false);
    const [canceling, setCanceling] = useState(false);

    const { isAuthenticated } = useAuth();

    const loadQuests = useCallback(async () => {
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
    }, [selectedClass, selectedDifficulty]);

    const loadMyQuests = useCallback(async () => {
        if (!isAuthenticated) {
            setMyAcceptances([]);
            setRankProgress(null);
            return;
        }
        try {
            const data = await fetchMyQuests();
            setMyAcceptances(data.acceptances ?? []);
            setRankProgress(data.rank_progress ?? null);
        } catch {
            // Silent fail — user might not have an adventurer profile
        }
    }, [isAuthenticated]);

    useEffect(() => {
        loadQuests();
    }, [loadQuests]);

    useEffect(() => {
        loadMyQuests();
    }, [loadMyQuests]);

    // Build a map of quest_ref -> acceptance status
    const acceptanceMap = useMemo(() => {
        const map = new Map<string, QuestAcceptance>();
        myAcceptances.forEach((a) => map.set(a.quest_ref, a));
        return map;
    }, [myAcceptances]);

    // Derive the quest_ref for a quest
    const getQuestRef = useCallback((quest: Quest): string => {
        return quest.quest_code ?? `quest-${quest.id}`;
    }, []);

    const getAcceptanceForQuest = useCallback((quest: Quest): QuestAcceptance | null => {
        return acceptanceMap.get(getQuestRef(quest)) ?? null;
    }, [acceptanceMap, getQuestRef]);

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
            const hasMissingChainedDependencies = dependencyType === 'chained' && dependsOnRefs.length === 0;

            const unresolved = dependsOnRefs
                .map((ref) => {
                    const depQuest = questRefMap.get(normalizeRef(ref));
                    if (!depQuest || depQuest.id === quest.id) {
                        return ref;
                    }

                    const depAcceptance = getAcceptanceForQuest(depQuest);
                    const isCompleted = depAcceptance?.status === 'completed';
                    return isCompleted ? null : depQuest.title;
                })
                .filter((label): label is string => Boolean(label));

            let blocked = false;
            let reason: string | null = null;

            if (dependencyType === 'blocked') {
                blocked = true;
                reason = quest.unlock_condition ?? (unresolved.length > 0 ? `Requires: ${unresolved.join(', ')}` : 'Blocked by dependency rules.');
            } else if (hasMissingChainedDependencies) {
                blocked = true;
                reason = quest.unlock_condition ?? 'Locked: chained quest is missing dependency links.';
            } else if (dependencyType === 'chained' && unresolved.length > 0) {
                blocked = true;
                reason = `Complete first: ${unresolved.join(', ')}`;
            }

            statusMap.set(quest.id, { blocked, unresolved, reason });
        });

        return statusMap;
    }, [questRefMap, quests, getAcceptanceForQuest]);

    const getDependencyStatus = useCallback((quest: Quest): DependencyStatus => (
        dependencyStatusByQuestId.get(quest.id) ?? { blocked: false, unresolved: [], reason: null }
    ), [dependencyStatusByQuestId]);

    const getQuestLevel = useCallback((quest: Quest): number => quest.quest_level ?? quest.difficulty ?? 1, []);
    const getQuestRank = useCallback((quest: Quest): string => {
        if (quest.rank_required) return String(quest.rank_required).toLowerCase();
        const level = getQuestLevel(quest);
        if (level <= 1) return 'iron';
        if (level === 2) return 'silver';
        if (level === 3) return 'gold';
        if (level === 4) return 'jade';
        return 'diamond';
    }, [getQuestLevel]);

    const getRankIndex = (rank: string): number => {
        const idx = RANK_ORDER.indexOf(rank);
        return idx === -1 ? Number.MAX_SAFE_INTEGER : idx;
    };

    const sortedQuests = useMemo(() => (
        [...quests].sort((a, b) => {
            const levelDiff = getQuestLevel(a) - getQuestLevel(b);
            if (levelDiff !== 0) return levelDiff;

            const rankDiff = getRankIndex(getQuestRank(a)) - getRankIndex(getQuestRank(b));
            if (rankDiff !== 0) return rankDiff;

            const numberDiff = a.number - b.number;
            if (numberDiff !== 0) return numberDiff;

            return a.id - b.id;
        })
    ), [quests, getQuestLevel, getQuestRank]);

    const visibleQuests = useMemo(
        () => sortedQuests.filter((quest) => !getDependencyStatus(quest).blocked),
        [sortedQuests, getDependencyStatus]
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

    const handleSubmitQuestPR = async (quest: Quest) => {
        const questRef = getQuestRef(quest);
        const prUrl = window.prompt('Paste your GitHub PR URL (example: https://github.com/org/repo/pull/123)');
        if (!prUrl) return;

        const normalized = prUrl.trim();
        const isValidGitHubPr = /^https:\/\/github\.com\/[^/]+\/[^/]+\/pull\/\d+$/i.test(normalized);
        if (!isValidGitHubPr) {
            window.alert('Please enter a valid GitHub PR URL.');
            return;
        }

        setSubmitting(true);
        try {
            await submitQuest(questRef, normalized);
            await loadMyQuests();
            window.alert('PR submitted for review.');
        } catch (err) {
            window.alert(err instanceof Error ? err.message : 'Failed to submit PR.');
        } finally {
            setSubmitting(false);
        }
    };

    const handleCancelQuest = async (quest: Quest) => {
        const questRef = getQuestRef(quest);
        const confirmCancel = window.confirm('Cancel this quest? You can accept it again later.');
        if (!confirmCancel) return;

        setCanceling(true);
        try {
            await cancelQuest(questRef);
            await loadMyQuests();
            window.alert('Quest canceled.');
        } catch (err) {
            window.alert(err instanceof Error ? err.message : 'Failed to cancel quest.');
        } finally {
            setCanceling(false);
        }
    };

    const resolveDependencyTitle = useCallback((ref: string): string => {
        const depQuest = questRefMap.get(normalizeRef(ref));
        return depQuest ? depQuest.title : ref;
    }, [questRefMap]);

    return {
        visibleQuests,
        loading,
        error,
        selectedClass,
        setSelectedClass,
        selectedDifficulty,
        setSelectedDifficulty,
        selectedQuest,
        setSelectedQuest,
        rankProgress,
        accepting,
        submitting,
        canceling,
        getDependencyStatus,
        getAcceptanceForQuest,
        handleAcceptQuest,
        handleSubmitQuestPR,
        handleCancelQuest,
        loadQuests,
        resolveDependencyTitle,
        isAuthenticated
    };
};
