import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../stores/authStore';
import { useToastStore } from '../stores/toastStore';
import { fetchAdventurer } from '../api/adventurerApi';
import { Adventurer } from '../types/Adventurer';

export const XPWidget: React.FC = () => {
  const { user, isAuthenticated } = useAuth();
  const { success } = useToastStore();
  const [adventurer, setAdventurer] = useState<Adventurer | null>(null);

  useEffect(() => {
    const loadProfile = async () => {
      if (isAuthenticated && user?.username) {
        try {
          const data = await fetchAdventurer(user.username);
          setAdventurer(data);

          const storedLevel = localStorage.getItem(
            `adv_level_${user.username}`
          );
          if (storedLevel) {
            const oldLevel = parseInt(storedLevel, 10);
            if (data.level > oldLevel) {
              success(`Level up! You reached Level ${data.level}.`);
            }
          }
          localStorage.setItem(
            `adv_level_${user.username}`,
            data.level.toString()
          );
        } catch {
          // Silent fail
        }
      }
    };

    loadProfile();
  }, [isAuthenticated, user?.username, success]);

  if (!isAuthenticated || !adventurer) return null;

  const nextLevelXP = adventurer.level * 100 * 1.5;
  const progressPercent = Math.min(
    100,
    ((adventurer.xp_total % nextLevelXP) / nextLevelXP) * 100
  );

  return (
    <Link
      to={`/adventurers/${adventurer.github_username}`}
      className="inline-flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5 transition-colors hover:border-slate-700 hover:bg-slate-800"
      title={`${adventurer.xp_total.toLocaleString()} Total XP`}
    >
      <span className="inline-flex min-w-6 items-center justify-center rounded-md bg-cyan-500/20 px-1.5 py-0.5 text-xs font-semibold text-cyan-200 ring-1 ring-cyan-500/30">
        {adventurer.level}
      </span>
      <div className="w-20">
        <div className="mb-0.5 truncate text-[10px] font-semibold leading-none text-slate-300">
          {adventurer.class.replace(/-/g, ' ')}
        </div>
        <div className="h-1.5 w-full overflow-hidden rounded-full border border-slate-700/70 bg-slate-950">
          <div
            className="h-1.5 rounded-full bg-gradient-to-r from-cyan-500 to-blue-500"
            style={{ width: `${progressPercent}%` }}
          />
        </div>
      </div>
    </Link>
  );
};
