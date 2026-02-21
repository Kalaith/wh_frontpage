import React, { useEffect, useState } from 'react';
import { heistApi, WeeklyHeist as WeeklyHeistData } from '../api/heistApi';

const WeeklyHeist: React.FC = () => {
  const [heist, setHeist] = useState<WeeklyHeistData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    const fetchHeist = async () => {
      setIsLoading(true);
      const currentHeist = await heistApi.fetchCurrentHeist();
      setHeist(currentHeist);
      setIsLoading(false);
    };
    fetchHeist();
  }, []);

  if (isLoading) {
    return (
      <div className="bg-white rounded-xl shadow-sm border-l-4 border-emerald-500 p-8 flex justify-center items-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
      </div>
    );
  }

  if (!heist) return null;

  const progress = Math.min(100, (heist.current / heist.target) * 100);
  const daysLeft = Math.max(
    0,
    Math.ceil((new Date(heist.ends_at).getTime() - Date.now()) / 86400000)
  );

  return (
    <div className="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow overflow-hidden border-l-4 border-emerald-500">
      <div className="p-4 sm:p-5">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 gap-2">
          <div>
            <div className="flex items-center gap-2 mb-1">
              <span className="text-xl sm:text-2xl">🏴‍☠️</span>
              <h3 className="text-lg sm:text-xl font-bold text-slate-900">
                Weekly Heist
              </h3>
            </div>
            <p className="text-emerald-700 font-medium text-xs sm:text-sm">
              Team effort — everyone wins together!
            </p>
          </div>
          <div className="text-left sm:text-right">
            <div className="text-[10px] sm:text-xs text-slate-500 uppercase font-bold tracking-wide">
              Time Left
            </div>
            <div className="text-xl sm:text-2xl font-bold text-slate-800">
              {daysLeft}d
            </div>
          </div>
        </div>

        {/* Goal */}
        <div className="bg-emerald-50 border border-emerald-100 rounded-lg p-3 sm:p-4 mb-4">
          <div className="text-[10px] sm:text-xs uppercase font-bold text-emerald-600 mb-1 tracking-wide">
            Mission Objective
          </div>
          <div className="font-bold text-base sm:text-lg text-slate-900">
            {heist.goal}
          </div>
        </div>

        {/* Progress Bar */}
        <div className="mb-4">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-end text-xs sm:text-sm mb-2 gap-1">
            <span className="font-semibold text-slate-700">Progress</span>
            <span className="font-mono font-bold text-emerald-700 break-words">
              {heist.current} / {heist.target} Quests Completed.
            </span>
          </div>
          <div className="w-full bg-gray-100 rounded-full h-4 overflow-hidden shadow-inner flex">
            <div
              className="bg-gradient-to-r from-emerald-400 to-teal-500 h-4 rounded-full transition-all duration-1000 ease-out relative shadow-sm"
              style={{ width: `${progress}%` }}
            >
              <div className="absolute inset-0 bg-white/30 animate-pulse rounded-full"></div>
            </div>
          </div>
        </div>

        {/* Stats Row */}
        <div className="flex flex-col sm:flex-row flex-wrap justify-between items-start sm:items-center text-xs sm:text-sm pt-3 border-t border-gray-100 gap-2">
          <div className="flex items-center gap-1.5 font-medium text-slate-600">
            <span>👥</span>
            <span>{heist.participants} participants</span>
          </div>
          <div className="flex items-start sm:items-center gap-1.5 font-medium text-amber-600 text-left">
            <span className="mt-0.5 sm:mt-0">🏆</span>
            <span className="text-[10px] sm:text-xs break-words">
              {heist.reward}
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default WeeklyHeist;
