import { create } from 'zustand';
import { fetchSystemClasses, AdventurerClass } from '../api/systemApi';

interface SystemState {
  classes: AdventurerClass[];
  loadingClasses: boolean;
  errorClasses: string | null;
  loadClasses: () => Promise<void>;
  getClassLabel: (id: string) => string;
  getClassIcon: (id: string) => string;
}

export const useSystemStore = create<SystemState>((set, get) => ({
  classes: [],
  loadingClasses: false,
  errorClasses: null,

  loadClasses: async () => {
    // Only load if not already loaded or loading
    if (get().classes.length > 0 || get().loadingClasses) return;

    set({ loadingClasses: true, errorClasses: null });
    try {
      const classes = await fetchSystemClasses();
      set({ classes, loadingClasses: false });
    } catch (err: unknown) {
      set({
        errorClasses:
          err instanceof Error ? err.message : 'Failed to load system classes',
        loadingClasses: false,
      });
    }
  },

  getClassLabel: (id: string) => {
    const cls = get().classes.find(c => c.id === id);
    return cls ? cls.label : id.replace(/-/g, ' ');
  },

  getClassIcon: (id: string) => {
    const cls = get().classes.find(c => c.id === id);
    return cls ? cls.icon : '❓';
  },
}));
