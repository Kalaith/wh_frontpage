import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import api from '../api/api';
import type { Boss } from '../types/Boss';
import type { Project, ProjectsData } from '../types/projects';
import type { QuestChain, QuestChainStep } from '../types/QuestChain';
import { useAuth } from '../stores/authStore';

type PendingReview = {
  id: number;
  adventurer_id: number;
  quest_ref: string;
  github_username?: string | null;
  display_name?: string | null;
  username?: string | null;
  submitted_at?: string | null;
};

const QuestManagementPage: React.FC = () => {
  const { isAuthenticated, isAdmin, isLoading, loginWithRedirect } = useAuth();

  const [projects, setProjects] = useState<Project[]>([]);
  const [chains, setChains] = useState<QuestChain[]>([]);
  const [bosses, setBosses] = useState<Boss[]>([]);
  const [pendingReviews, setPendingReviews] = useState<PendingReview[]>([]);
  const [loadingData, setLoadingData] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);

  const [seedText, setSeedText] = useState('');
  const [clearExisting, setClearExisting] = useState(false);
  const [importing, setImporting] = useState(false);

  const [questForm, setQuestForm] = useState({
    projectId: '',
    title: '',
    description: '',
    questLevel: '1',
    xp: '20',
  });

  const [editChainSlug, setEditChainSlug] = useState('');
  const [editStepId, setEditStepId] = useState('');
  const [editStep, setEditStep] = useState<Partial<QuestChainStep>>({});
  const [savingStep, setSavingStep] = useState(false);

  const [bossForm, setBossForm] = useState({
    name: '',
    description: '',
    projectId: '',
    threatLevel: '3',
    hpTotal: '8',
    hpCurrent: '8',
    status: 'active',
  });
  const [savingBoss, setSavingBoss] = useState(false);
  const [savingReviewId, setSavingReviewId] = useState<number | null>(null);

  const selectedChain = useMemo(
    () => chains.find(c => c.slug === editChainSlug) ?? null,
    [chains, editChainSlug]
  );
  const selectedStep = useMemo(() => {
    if (!selectedChain || !editStepId) return null;
    return selectedChain.steps.find(step => String(step.id ?? '') === editStepId);
  }, [selectedChain, editStepId]);

  const loadData = async () => {
    setLoadingData(true);
    setError(null);
    try {
      const [projectsRes, chainsRes, bossesRes, reviewsRes] = await Promise.all([
        api.getProjects(),
        api.request<QuestChain[]>('/quest-chains'),
        api.getAdminBosses(),
        api.getPendingQuestReviews(),
      ]);

      if (!projectsRes.success || !projectsRes.data) {
        throw new Error('Failed to load projects');
      }
      if (!chainsRes.success || !chainsRes.data) {
        throw new Error('Failed to load quest chains');
      }
      if (!bossesRes.success) {
        throw new Error('Failed to load bosses');
      }
      if (!reviewsRes.success) {
        throw new Error('Failed to load pending reviews');
      }

      const pdata = projectsRes.data as ProjectsData;
      setProjects(Array.isArray(pdata.projects) ? pdata.projects : []);
      setChains(Array.isArray(chainsRes.data) ? chainsRes.data : []);
      setBosses(Array.isArray(bossesRes.data) ? (bossesRes.data as Boss[]) : []);
      setPendingReviews(
        Array.isArray(reviewsRes.data)
          ? (reviewsRes.data as PendingReview[])
          : []
      );
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load data');
    } finally {
      setLoadingData(false);
    }
  };

  useEffect(() => {
    if (!isAuthenticated || !isAdmin) return;
    void loadData();
  }, [isAuthenticated, isAdmin]);

  useEffect(() => {
    if (!selectedStep) return;
    setEditStep({
      title: selectedStep.title ?? '',
      description: selectedStep.description ?? '',
      class: selectedStep.class ?? '',
      quest_level: selectedStep.quest_level ?? selectedStep.difficulty ?? 1,
      difficulty: selectedStep.difficulty ?? selectedStep.quest_level ?? 1,
      xp: selectedStep.xp ?? 20,
      rank_required: selectedStep.rank_required ?? 'Iron',
      due_date: selectedStep.due_date ?? '',
    });
  }, [selectedStep]);

  const onSeedFilePicked = async (file: File | null) => {
    if (!file) return;
    const text = await file.text();
    setSeedText(text);
  };

  const importSeed = async () => {
    setNotice(null);
    setError(null);
    let parsed: Record<string, unknown>;
    try {
      parsed = JSON.parse(seedText) as Record<string, unknown>;
    } catch {
      setError('Seed JSON is invalid');
      return;
    }
    setImporting(true);
    try {
      const res = await api.importQuestSeed(parsed, clearExisting);
      if (!res.success) {
        throw new Error(
          typeof res.error === 'string'
            ? res.error
            : (res.error?.message ?? 'Import failed')
        );
      }
      setNotice('Quest seed imported successfully');
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Import failed');
    } finally {
      setImporting(false);
    }
  };

  const createSingleQuest = async () => {
    setNotice(null);
    setError(null);
    const projectId = Number(questForm.projectId);
    if (!projectId || !questForm.title.trim() || !questForm.description.trim()) {
      setError('Project, title, and description are required');
      return;
    }

    const res = await api.createProjectQuest(projectId, {
      title: questForm.title.trim(),
      description: questForm.description.trim(),
      quest_level: Number(questForm.questLevel) || 1,
      xp: Number(questForm.xp) || 20,
    });
    if (!res.success) {
      setError(
        typeof res.error === 'string'
          ? res.error
          : (res.error?.message ?? 'Failed to create quest')
      );
      return;
    }

    setQuestForm({
      projectId: questForm.projectId,
      title: '',
      description: '',
      questLevel: '1',
      xp: '20',
    });
    setNotice('Quest created');
    await loadData();
  };

  const saveStep = async () => {
    if (!editChainSlug || !editStepId) {
      setError('Select a chain and quest first');
      return;
    }
    setSavingStep(true);
    setNotice(null);
    setError(null);
    try {
      const res = await api.updateQuestStep(editChainSlug, editStepId, {
        title: (editStep.title ?? '').toString(),
        description: (editStep.description ?? '').toString(),
        class: (editStep.class ?? '').toString(),
        quest_level: Number(editStep.quest_level ?? 1),
        difficulty: Number(editStep.difficulty ?? 1),
        xp: Number(editStep.xp ?? 20),
        rank_required: (editStep.rank_required ?? 'Iron').toString(),
        due_date: (editStep.due_date ?? '').toString(),
      });
      if (!res.success) {
        throw new Error(
          typeof res.error === 'string'
            ? res.error
            : (res.error?.message ?? 'Failed to update quest step')
        );
      }
      setNotice('Quest step updated');
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update quest');
    } finally {
      setSavingStep(false);
    }
  };

  const createBoss = async () => {
    if (!bossForm.name.trim()) {
      setError('Boss name is required');
      return;
    }
    setSavingBoss(true);
    setNotice(null);
    setError(null);
    try {
      const res = await api.createBoss({
        name: bossForm.name.trim(),
        description: bossForm.description.trim(),
        project_id: bossForm.projectId ? Number(bossForm.projectId) : null,
        threat_level: Number(bossForm.threatLevel) || 3,
        hp_total: Number(bossForm.hpTotal) || 8,
        hp_current: Number(bossForm.hpCurrent) || Number(bossForm.hpTotal) || 8,
        status: bossForm.status,
      });
      if (!res.success) {
        throw new Error(
          typeof res.error === 'string'
            ? res.error
            : (res.error?.message ?? 'Failed to create boss')
        );
      }
      setNotice('Boss created');
      setBossForm({
        name: '',
        description: '',
        projectId: bossForm.projectId,
        threatLevel: '3',
        hpTotal: '8',
        hpCurrent: '8',
        status: 'active',
      });
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to create boss');
    } finally {
      setSavingBoss(false);
    }
  };

  const updateBossStatus = async (boss: Boss, status: string) => {
    const res = await api.updateBoss(boss.id, { status });
    if (!res.success) {
      setError(
        typeof res.error === 'string'
          ? res.error
          : (res.error?.message ?? 'Failed to update boss')
      );
      return;
    }
    setNotice(`Boss "${boss.name}" updated`);
    await loadData();
  };

  const completeReview = async (item: PendingReview) => {
    setSavingReviewId(item.id);
    setError(null);
    setNotice(null);
    try {
      const res = await api.completeQuestReview({
        quest_ref: item.quest_ref,
        adventurer_id: item.adventurer_id,
        xp: 20,
        review_notes: 'Completed by admin via Quest Manager',
      });
      if (!res.success) {
        throw new Error(
          typeof res.error === 'string'
            ? res.error
            : (res.error?.message ?? 'Failed to complete quest')
        );
      }
      setNotice(`Marked ${item.quest_ref} as completed`);
      await loadData();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to complete quest');
    } finally {
      setSavingReviewId(null);
    }
  };

  if (isLoading) {
    return (
      <div className="max-w-5xl mx-auto p-6">
        <p>Loading authentication...</p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="max-w-5xl mx-auto p-6">
        <h2 className="text-xl font-semibold mb-3">Authentication Required</h2>
        <button
          onClick={() => loginWithRedirect()}
          className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"
        >
          Log In
        </button>
      </div>
    );
  }

  if (!isAdmin) {
    return (
      <div className="max-w-5xl mx-auto p-6">
        <h2 className="text-xl font-semibold mb-3">Admin Access Required</h2>
        <Link className="text-blue-700 underline" to="/">
          Return Home
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto p-6 space-y-8">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Quest Manager</h1>
        <button
          onClick={() => void loadData()}
          className="px-3 py-2 border rounded bg-white hover:bg-gray-50"
        >
          Refresh
        </button>
      </div>

      {loadingData && <p>Loading quest management data...</p>}
      {error && <p className="text-red-600">{error}</p>}
      {notice && <p className="text-green-700">{notice}</p>}

      <section className="bg-white border rounded p-4 space-y-3">
        <h2 className="text-lg font-medium">Import Quest Seed JSON</h2>
        <input
          type="file"
          accept=".json,application/json"
          onChange={e => void onSeedFilePicked(e.target.files?.[0] ?? null)}
        />
        <textarea
          value={seedText}
          onChange={e => setSeedText(e.target.value)}
          placeholder="Paste seed JSON here"
          className="w-full min-h-44 border rounded p-2 font-mono text-sm"
        />
        <label className="inline-flex items-center gap-2">
          <input
            type="checkbox"
            checked={clearExisting}
            onChange={e => setClearExisting(e.target.checked)}
          />
          Clear existing matching chains/bosses before import
        </label>
        <div>
          <button
            onClick={() => void importSeed()}
            disabled={importing || !seedText.trim()}
            className="px-4 py-2 rounded bg-blue-600 text-white disabled:opacity-50"
          >
            {importing ? 'Importing...' : 'Import Seed'}
          </button>
        </div>
      </section>

      <section className="bg-white border rounded p-4 space-y-3">
        <h2 className="text-lg font-medium">Insert Single Quest</h2>
        <div className="grid md:grid-cols-2 gap-3">
          <select
            className="border rounded p-2"
            value={questForm.projectId}
            onChange={e =>
              setQuestForm(prev => ({ ...prev, projectId: e.target.value }))
            }
          >
            <option value="">Select project</option>
            {projects.map(p => (
              <option key={p.id ?? p.title} value={p.id ?? ''}>
                {p.title} {p.id ? `(#${p.id})` : ''}
              </option>
            ))}
          </select>
          <input
            className="border rounded p-2"
            value={questForm.title}
            onChange={e =>
              setQuestForm(prev => ({ ...prev, title: e.target.value }))
            }
            placeholder="Quest title"
          />
          <input
            className="border rounded p-2"
            value={questForm.questLevel}
            onChange={e =>
              setQuestForm(prev => ({ ...prev, questLevel: e.target.value }))
            }
            placeholder="Quest level"
          />
          <input
            className="border rounded p-2"
            value={questForm.xp}
            onChange={e =>
              setQuestForm(prev => ({ ...prev, xp: e.target.value }))
            }
            placeholder="XP"
          />
        </div>
        <textarea
          className="w-full border rounded p-2"
          value={questForm.description}
          onChange={e =>
            setQuestForm(prev => ({ ...prev, description: e.target.value }))
          }
          placeholder="Quest description"
        />
        <button
          onClick={() => void createSingleQuest()}
          className="px-4 py-2 rounded bg-emerald-600 text-white"
        >
          Create Quest
        </button>
      </section>

      <section className="bg-white border rounded p-4 space-y-3">
        <h2 className="text-lg font-medium">Edit Quest</h2>
        <div className="grid md:grid-cols-2 gap-3">
          <select
            className="border rounded p-2"
            value={editChainSlug}
            onChange={e => {
              setEditChainSlug(e.target.value);
              setEditStepId('');
            }}
          >
            <option value="">Select chain</option>
            {chains.map(c => (
              <option key={c.slug} value={c.slug}>
                {c.name}
              </option>
            ))}
          </select>
          <select
            className="border rounded p-2"
            value={editStepId}
            onChange={e => setEditStepId(e.target.value)}
            disabled={!selectedChain}
          >
            <option value="">Select quest</option>
            {(selectedChain?.steps ?? []).map((step, idx) => (
              <option key={`${step.id ?? idx}`} value={String(step.id ?? '')}>
                {step.id ?? `step-${idx + 1}`} - {step.title}
              </option>
            ))}
          </select>
          <input
            className="border rounded p-2"
            value={(editStep.title ?? '').toString()}
            onChange={e => setEditStep(prev => ({ ...prev, title: e.target.value }))}
            placeholder="Title"
            disabled={!editStepId}
          />
          <input
            className="border rounded p-2"
            value={(editStep.class ?? '').toString()}
            onChange={e => setEditStep(prev => ({ ...prev, class: e.target.value }))}
            placeholder="Class"
            disabled={!editStepId}
          />
          <input
            className="border rounded p-2"
            value={String(editStep.quest_level ?? '')}
            onChange={e =>
              setEditStep(prev => ({ ...prev, quest_level: Number(e.target.value) || 1 }))
            }
            placeholder="Quest level"
            disabled={!editStepId}
          />
          <input
            className="border rounded p-2"
            value={String(editStep.xp ?? '')}
            onChange={e => setEditStep(prev => ({ ...prev, xp: Number(e.target.value) || 0 }))}
            placeholder="XP"
            disabled={!editStepId}
          />
        </div>
        <textarea
          className="w-full border rounded p-2"
          value={(editStep.description ?? '').toString()}
          onChange={e =>
            setEditStep(prev => ({ ...prev, description: e.target.value }))
          }
          placeholder="Description"
          disabled={!editStepId}
        />
        <button
          onClick={() => void saveStep()}
          disabled={savingStep || !editChainSlug || !editStepId}
          className="px-4 py-2 rounded bg-amber-600 text-white disabled:opacity-50"
        >
          {savingStep ? 'Saving...' : 'Save Quest Changes'}
        </button>
      </section>

      <section className="bg-white border rounded p-4 space-y-3">
        <h2 className="text-lg font-medium">Bosses</h2>
        <div className="grid md:grid-cols-3 gap-3">
          <input
            className="border rounded p-2"
            value={bossForm.name}
            onChange={e => setBossForm(prev => ({ ...prev, name: e.target.value }))}
            placeholder="Boss name"
          />
          <select
            className="border rounded p-2"
            value={bossForm.projectId}
            onChange={e =>
              setBossForm(prev => ({ ...prev, projectId: e.target.value }))
            }
          >
            <option value="">No project</option>
            {projects.map(p => (
              <option key={`boss-p-${p.id ?? p.title}`} value={p.id ?? ''}>
                {p.title} {p.id ? `(#${p.id})` : ''}
              </option>
            ))}
          </select>
          <select
            className="border rounded p-2"
            value={bossForm.status}
            onChange={e => setBossForm(prev => ({ ...prev, status: e.target.value }))}
          >
            <option value="active">active</option>
            <option value="stabilizing">stabilizing</option>
            <option value="defeated">defeated</option>
          </select>
          <input
            className="border rounded p-2"
            value={bossForm.threatLevel}
            onChange={e =>
              setBossForm(prev => ({ ...prev, threatLevel: e.target.value }))
            }
            placeholder="Threat level"
          />
          <input
            className="border rounded p-2"
            value={bossForm.hpTotal}
            onChange={e => setBossForm(prev => ({ ...prev, hpTotal: e.target.value }))}
            placeholder="HP total"
          />
          <input
            className="border rounded p-2"
            value={bossForm.hpCurrent}
            onChange={e =>
              setBossForm(prev => ({ ...prev, hpCurrent: e.target.value }))
            }
            placeholder="HP current"
          />
        </div>
        <textarea
          className="w-full border rounded p-2"
          value={bossForm.description}
          onChange={e =>
            setBossForm(prev => ({ ...prev, description: e.target.value }))
          }
          placeholder="Boss description"
        />
        <button
          onClick={() => void createBoss()}
          disabled={savingBoss}
          className="px-4 py-2 rounded bg-rose-700 text-white disabled:opacity-50"
        >
          {savingBoss ? 'Creating...' : 'Create Boss'}
        </button>

        <div className="space-y-2">
          {bosses.map(boss => (
            <div
              key={boss.id}
              className="border rounded p-3 flex items-center justify-between gap-3"
            >
              <div>
                <div className="font-medium">{boss.name}</div>
                <div className="text-sm text-gray-600">
                  {boss.project_name ?? 'No project'} | HP {boss.hp_current}/
                  {boss.hp_total}
                </div>
              </div>
              <select
                className="border rounded p-2"
                value={boss.status}
                onChange={e => void updateBossStatus(boss, e.target.value)}
              >
                <option value="active">active</option>
                <option value="stabilizing">stabilizing</option>
                <option value="defeated">defeated</option>
              </select>
            </div>
          ))}
        </div>
      </section>

      <section className="bg-white border rounded p-4 space-y-3">
        <h2 className="text-lg font-medium">Pending Quest Reviews</h2>
        {pendingReviews.length === 0 && (
          <p className="text-gray-600">No submitted quests waiting for review.</p>
        )}
        {pendingReviews.map(item => (
          <div
            key={item.id}
            className="border rounded p-3 flex items-center justify-between gap-3"
          >
            <div>
              <div className="font-medium">{item.quest_ref}</div>
              <div className="text-sm text-gray-600">
                Adventurer #{item.adventurer_id}
                {item.display_name ? ` (${item.display_name})` : ''}
                {item.github_username ? ` | ${item.github_username}` : ''}
              </div>
            </div>
            <button
              onClick={() => void completeReview(item)}
              disabled={savingReviewId === item.id}
              className="px-3 py-2 rounded bg-indigo-600 text-white disabled:opacity-50"
            >
              {savingReviewId === item.id ? 'Completing...' : 'Mark Complete (+20 XP)'}
            </button>
          </div>
        ))}
      </section>
    </div>
  );
};

export default QuestManagementPage;
