import React, { useEffect, useState } from 'react';
import { fetchQuests } from '../api/questApi';
import { Quest } from '../types/Quest';
import { QuestCard } from '../components/QuestCard';

const QuestBoardPage: React.FC = () => {
    const [quests, setQuests] = useState<Quest[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [selectedClass, setSelectedClass] = useState<string>('');
    const [selectedDifficulty, setSelectedDifficulty] = useState<number>(0);

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

    useEffect(() => {
        loadQuests();
    }, [selectedClass, selectedDifficulty]);

    const projectClasses = [
        { id: 'bug-hunter', label: 'Bug Hunter' },
        { id: 'patch-crafter', label: 'Patch Crafter' },
        { id: 'feature-smith', label: 'Feature Smith' },
        { id: 'doc-sage', label: 'Doc Sage' },
        { id: 'ux-alchemist', label: 'UX Alchemist' },
        { id: 'ops-ranger', label: 'Ops Ranger' },
        { id: 'test-summoner', label: 'Test Summoner' },
    ];

    return (
        <div className="container mx-auto px-4 py-8">
            <header className="mb-8 text-center">
                <h1 className="text-4xl font-extrabold text-gray-900 mb-2">
                    <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
                        Quest Board
                    </span>
                </h1>
                <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                    Choose your path, adventurer. Claim tasks, earn XP, and level up your mastery.
                </p>
            </header>

            {/* Filters */}
            <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-100 mb-8 flex flex-wrap gap-4 items-center justify-center">
                <div className="flex items-center gap-2">
                    <label className="font-semibold text-gray-700">Class:</label>
                    <select
                        value={selectedClass}
                        onChange={(e) => setSelectedClass(e.target.value)}
                        className="form-select rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    >
                        <option value="">All Classes</option>
                        {projectClasses.map(c => (
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
                        <option value="1">⭐ Hatchling (Tutorial)</option>
                        <option value="2">⭐⭐ Easy</option>
                        <option value="3">⭐⭐⭐ Standard</option>
                        <option value="4">⭐⭐⭐⭐ Hard</option>
                        <option value="5">👑 Raid (Team)</option>
                    </select>
                </div>
            </div>

            {/* Content */}
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
                    {quests.map(quest => (
                        <QuestCard key={quest.id} quest={quest} />
                    ))}
                </div>
            )}
        </div>
    );
};

export default QuestBoardPage;
