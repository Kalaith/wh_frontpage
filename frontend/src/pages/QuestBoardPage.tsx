import React, { useEffect } from 'react';
import { QuestCard } from '../components/QuestCard';
import { RankProgressBar } from '../components/RankProgressBar';
import { QuestDetailModal } from '../components/QuestDetailModal';
import { useQuestBoard } from '../hooks/useQuestBoard';
import { useSystemStore } from '../stores/systemStore';

const QuestBoardPage: React.FC = () => {
  const { classes, loadClasses } = useSystemStore();

  useEffect(() => {
    loadClasses();
  }, [loadClasses]);
  const {
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
    isAuthenticated,
  } = useQuestBoard();

  return (
    <div className="container mx-auto px-4 py-6">
      <header className="text-center">
        <h1 className="text-2xl font-bold text-gray-900">
          <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
            Quest Board
          </span>
        </h1>
        <p className="mt-1 text-sm text-gray-600">
          Pick a quest and start building.
        </p>
      </header>

      {/* Rank Progress Bar */}
      {isAuthenticated && <RankProgressBar rankProgress={rankProgress} />}

      <div className="mb-4 flex flex-wrap items-center justify-center gap-2 text-xs">
        <span className="font-semibold text-gray-600 mr-1">Rank Colors:</span>
        <span className="px-2 py-1 rounded border border-stone-200 bg-stone-50 text-stone-700">
          Iron
        </span>
        <span className="px-2 py-1 rounded border border-slate-200 bg-slate-50 text-slate-700">
          Silver
        </span>
        <span className="px-2 py-1 rounded border border-amber-200 bg-amber-50 text-amber-700">
          Gold
        </span>
        <span className="px-2 py-1 rounded border border-emerald-200 bg-emerald-50 text-emerald-700">
          Jade
        </span>
        <span className="px-2 py-1 rounded border border-cyan-200 bg-cyan-50 text-cyan-700">
          Diamond
        </span>
      </div>

      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-8 flex flex-wrap gap-4 items-center justify-center">
        <div className="flex items-center gap-2">
          <label className="font-semibold text-gray-700">Class:</label>
          <select
            value={selectedClass}
            onChange={e => setSelectedClass(e.target.value)}
            className="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
          >
            <option value="">All Classes</option>
            {classes
              .filter(c => c.id !== 'hatchling')
              .map(c => (
                <option key={c.id} value={c.id}>
                  {c.label}
                </option>
              ))}
          </select>
        </div>

        <div className="flex items-center gap-2">
          <label className="font-semibold text-gray-700">Difficulty:</label>
          <select
            value={selectedDifficulty}
            onChange={e => setSelectedDifficulty(Number(e.target.value))}
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
      ) : visibleQuests.length === 0 ? (
        <div className="text-center py-12 bg-gray-50 rounded-lg border border-gray-200 text-gray-500">
          <p className="text-xl font-medium mb-2">
            No quests are currently unlocked.
          </p>
          <p>
            🧭 Explore the active projects or complete your current missions to
            reveal new bounties.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {visibleQuests.map(quest => (
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

      {/* Quest Details Modal */}
      {selectedQuest && (
        <QuestDetailModal
          quest={selectedQuest}
          status={getDependencyStatus(selectedQuest)}
          acceptance={getAcceptanceForQuest(selectedQuest)}
          resolveDependencyTitle={resolveDependencyTitle}
          onClose={() => setSelectedQuest(null)}
          onAccept={handleAcceptQuest}
          onSubmitPR={handleSubmitQuestPR}
          onCancel={handleCancelQuest}
          accepting={accepting}
          submitting={submitting}
          canceling={canceling}
        />
      )}
    </div>
  );
};

export default QuestBoardPage;
