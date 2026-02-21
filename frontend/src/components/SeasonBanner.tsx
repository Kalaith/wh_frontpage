import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../stores/authStore';
import { fetchAdventurer } from '../api/adventurerApi';
import { Adventurer } from '../types/Adventurer';

export const SeasonBanner: React.FC = () => {
  const { user, isAuthenticated } = useAuth();
  const [adventurer, setAdventurer] = useState<Adventurer | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const loadProfile = async () => {
      if (isAuthenticated && user?.username) {
        setLoading(true);
        try {
          // Assuming local username matches GitHub username for now
          const data = await fetchAdventurer(user.username);
          setAdventurer(data);
        } catch {
          console.log('Adventurer profile not found for user', user.username);
        } finally {
          setLoading(false);
        }
      }
    };
    loadProfile();
  }, [isAuthenticated, user?.username]);

  return (
    <div className="bg-white rounded-xl shadow-sm border border-indigo-100 overflow-hidden mb-8 relative border-l-4 border-l-indigo-500">
      {/* Background Pattern - Light subtle dots */}
      <div className="absolute inset-0 opacity-[0.03] bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjIiIGZpbGw9IiMwMDAiLz48L3N2Zz4=')]"></div>

      <div className="relative p-5 sm:p-6 lg:p-8 flex flex-col items-center justify-center gap-5">
        <div className="w-full text-center">
          <div className="inline-block bg-indigo-50 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full mb-3 uppercase tracking-wide border border-indigo-100">
            Season 1 Active
          </div>
          <h2 className="text-2xl sm:text-3xl font-bold mb-3 tracking-tight text-slate-900">
            The Awakening
          </h2>
          <p className="text-slate-600 text-sm sm:text-base mb-5">
            The codebases of Web Hatchery are stirring. New features are needed,
            bugs must be squashed. Will you answer the call?
          </p>

          <div className="flex flex-col gap-2">
            <Link
              to="/quests"
              className="w-full text-center py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm"
            >
              🔮 View Quests
            </Link>
            <Link
              to="/leaderboard"
              className="w-full text-center py-2 bg-white text-slate-700 font-medium rounded-lg hover:bg-slate-50 transition border border-gray-200 shadow-sm"
            >
              🏆 Leaderboard
            </Link>
            <Link
              to="/bosses"
              className="w-full text-center py-2 bg-rose-50 text-rose-700 font-medium rounded-lg hover:bg-rose-100 transition border border-rose-200"
            >
              ⚔️ Boss Battle
            </Link>
          </div>
        </div>

        {/* User Stats / Login Prompt */}
        <div className="w-full bg-gray-50 rounded-lg p-4 border border-gray-100 mt-2">
          {isAuthenticated && adventurer ? (
            <div>
              <div className="flex justify-between items-center mb-2">
                <Link
                  to={`/adventurers/${adventurer.github_username}`}
                  className="font-bold text-lg text-slate-900 hover:text-indigo-600 transition"
                >
                  {adventurer.github_username}
                </Link>
                <span className="text-xs bg-indigo-100 px-2 py-1 rounded text-indigo-800 uppercase font-bold tracking-wide border border-indigo-200">
                  Lv {adventurer.level}
                </span>
              </div>

              <div className="text-sm text-slate-500 mb-2 capitalize font-medium">
                {adventurer.class.replace(/-/g, ' ')}
              </div>

              <div className="w-full bg-gray-200 rounded-full h-2 mb-1 shadow-inner">
                <div
                  className="bg-gradient-to-r from-amber-400 to-amber-500 h-2 rounded-full shadow-sm"
                  style={{
                    width: `${Math.min(100, (adventurer.xp_total % (adventurer.level * 150)) / (adventurer.level * 1.5))}%`,
                  }}
                ></div>
              </div>
              <div className="text-right text-xs text-slate-500 font-mono font-medium">
                {adventurer.xp_total.toLocaleString()} XP
              </div>
            </div>
          ) : (
            <div className="text-center py-2">
              <p className="font-bold mb-1 text-slate-800">
                Not an Adventurer?
              </p>
              <p className="text-sm text-slate-500 mb-3">
                Login to track your progress!
              </p>
              {!isAuthenticated && (
                <Link
                  to="/login"
                  className="block w-full py-2 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg text-sm font-medium transition shadow-sm"
                >
                  Login / Register
                </Link>
              )}
              {isAuthenticated && !adventurer && !loading && (
                <p className="text-xs text-amber-600 mt-2 font-medium bg-amber-50 p-2 rounded border border-amber-100">
                  Tip: Set your username to match GitHub to sync!
                </p>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
