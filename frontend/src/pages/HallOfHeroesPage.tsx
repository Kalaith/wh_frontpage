import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import achievementsApi, { Achievement } from '../api/achievementsApi';

const RARITY_COLORS: Record<string, string> = {
  common: 'border-gray-300 bg-gray-50',
  rare: 'border-blue-400 bg-blue-50',
  epic: 'border-purple-500 bg-purple-50 shadow-md shadow-purple-200',
  legendary: 'border-yellow-500 bg-yellow-50 shadow-lg shadow-yellow-200',
};

const RARITY_TEXT: Record<string, string> = {
  common: 'text-gray-600',
  rare: 'text-blue-700',
  epic: 'text-purple-700',
  legendary: 'text-yellow-700',
};

const HallOfHeroesPage: React.FC = () => {
  const [achievements, setAchievements] = useState<Achievement[]>([]);
  const [filter, setFilter] = useState<string>('all');
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    const loadAchievements = async () => {
      setIsLoading(true);
      const data = await achievementsApi.fetchAll();
      setAchievements(data);
      setIsLoading(false);
    };
    loadAchievements();
  }, []);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[60vh]">
        <div className="animate-spin rounded-full h-12 w-12 border-4 border-indigo-600 border-t-transparent"></div>
      </div>
    );
  }

  const filtered =
    filter === 'all'
      ? achievements
      : achievements.filter(a => a.rarity === filter);
  const rarityOrder = ['legendary', 'epic', 'rare', 'common'];
  const sorted = [...filtered].sort(
    (a, b) => rarityOrder.indexOf(a.rarity) - rarityOrder.indexOf(b.rarity)
  );

  return (
    <div className="max-w-5xl mx-auto px-4 py-8">
      {/* Hero */}
      <div className="text-center mb-10">
        <h1 className="text-4xl font-extrabold text-gray-900 mb-3">
          🏛️ Hall of Heroes
        </h1>
        <p className="text-lg text-gray-600 max-w-2xl mx-auto">
          Every achievement ever earned across all of Web Hatchery. Can you
          collect them all?
        </p>
      </div>

      {/* Filter Tabs */}
      <div className="flex justify-center gap-2 mb-8 flex-wrap">
        {['all', 'legendary', 'epic', 'rare', 'common'].map(tab => (
          <button
            key={tab}
            onClick={() => setFilter(tab)}
            className={`px-4 py-2 rounded-full text-sm font-semibold capitalize transition ${
              filter === tab
                ? 'bg-indigo-600 text-white shadow-md'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            }`}
          >
            {tab === 'all' ? '✨ All' : tab}
          </button>
        ))}
      </div>

      {/* Achievement Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {sorted.map(achievement => (
          <div
            key={achievement.slug}
            className={`border-2 rounded-xl p-5 transition-all duration-300 hover:scale-[1.02] hover:-translate-y-0.5 ${RARITY_COLORS[achievement.rarity]}`}
          >
            <div className="flex items-start gap-3">
              <div className="text-3xl flex-shrink-0">{achievement.icon}</div>
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-1">
                  <h3 className="font-bold text-gray-900 truncate">
                    {achievement.name}
                  </h3>
                  <span
                    className={`text-xs font-bold uppercase px-1.5 py-0.5 rounded ${RARITY_TEXT[achievement.rarity]} bg-white/60`}
                  >
                    {achievement.rarity}
                  </span>
                </div>
                <p className="text-sm text-gray-600 mb-2">
                  {achievement.description}
                </p>
                <div className="text-xs text-gray-500">
                  {achievement.earnedBy > 0
                    ? `👤 Earned by ${achievement.earnedBy} adventurer${achievement.earnedBy > 1 ? 's' : ''}`
                    : '🔒 Not yet earned by anyone'}
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Quick Stats */}
      <div className="mt-10 bg-white border border-indigo-100 rounded-xl p-6 text-center shadow-sm border-l-4 border-l-indigo-500">
        <div className="grid grid-cols-3 gap-4">
          <div>
            <div className="text-3xl font-bold text-slate-800">
              {achievements.length}
            </div>
            <div className="text-sm text-slate-500 font-medium tracking-wide uppercase mt-1">
              Total Achievements
            </div>
          </div>
          <div>
            <div className="text-3xl font-bold text-emerald-600">
              {achievements.filter(a => a.earnedBy > 0).length}
            </div>
            <div className="text-sm text-emerald-700 font-medium tracking-wide uppercase mt-1">
              Unlocked
            </div>
          </div>
          <div>
            <div className="text-3xl font-bold text-slate-400">
              {achievements.filter(a => a.earnedBy === 0).length}
            </div>
            <div className="text-sm text-slate-500 font-medium tracking-wide uppercase mt-1">
              Still Locked
            </div>
          </div>
        </div>
      </div>

      {/* CTA */}
      <div className="mt-6 text-center">
        <Link
          to="/quests"
          className="inline-block px-6 py-3 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition shadow-lg"
        >
          🔮 Start Earning Achievements
        </Link>
      </div>
    </div>
  );
};

export default HallOfHeroesPage;
