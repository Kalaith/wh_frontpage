# WebHatchery Frontend Standards

This document establishes the mandatory standards for React/TypeScript projects.

## 📋 Core Technologies
- **React 19 & Vite**: Modern framework and fast build tool.
- **TypeScript**: Strict mode only. Use `class` for runtime exports (API), `type` for types.
- **Zustand**: State management with `persist` for local storage.
- **Tailwind CSS & Framer Motion**: Styling and animations.
- **React Router & Axios**: Routing and API interaction.

---

## 🚨 Critical TypeScript Export Standards
**CRITICAL**: `interface` and `type` are stripped at runtime. For anything requiring runtime existence (like API response objects or Error classes that are imported as values), use `class`.

**✅ CORRECT: Use classes for runtime exports**
```typescript
export class ApiResponse<T = any> {
  success!: boolean;
  data?: T;
  error?: string;
}

export class ApiError extends Error {
  constructor(public message: string, public status: number = 500) {
    super(message);
    this.name = 'ApiError';
  }
}
```

---

## 📂 Project Structure & Naming
Organize code by feature/domain for scalability.

```
src/
├── api/        # Axios client & service calls (apiClient.ts, auth.ts)
├── components/ # ui/, layout/, game/ (PascalCase: GameBoard.tsx)
├── hooks/      # Reusable logic (camelCase: useGameLoop.ts)
├── stores/     # Zustand stores (camelCase: useAuthStore.ts)
├── types/      # TypeScript definitions (camelCase: game.ts)
├── data/       # Static game data (kebab-case: item-data.ts)
└── utils/      # Helper functions (camelCase: calculations.ts)
```

---

## ⚛️ Component Standards
- **Functional Components**: Use `React.FC` or simple functions.
- **Prop Typing**: Always interface your props.
- **Composition**: Prefer small, focused components over massive ones.

**✅ CORRECT Pattern**
```typescript
interface PlayerStatsProps {
  level: number;
  experience: number;
}

export const PlayerStats: React.FC<PlayerStatsProps> = ({ level, experience }) => {
  const progress = useMemo(() => calculateProgress(experience), [experience]);
  
  return (
    <div className="p-4 bg-slate-900 rounded-xl border border-slate-700">
      <h3 className="text-blue-400 font-bold">Level {level}</h3>
      <ProgressBar value={progress} />
    </div>
  );
};
```

---

## 🔐 State Management (Zustand + Persistence)
Standardize on the `persist` middleware for game progress and authentication.

```typescript
export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      login: (user, token) => set({ user, token }),
      logout: () => {
        set({ user: null, token: null });
        localStorage.removeItem('auth-storage');
        window.location.href = '/';
      },
    }),
    { name: 'auth-storage' }
  )
);
```

---

## 🌐 API Interaction (Axios Interceptors)
Attach Bearer tokens globally and handle unauthorized responses.

```typescript
const api = axios.create({ baseURL: import.meta.env.VITE_API_BASE_URL });

api.interceptors.request.use((config) => {
  const auth = JSON.parse(localStorage.getItem('auth-storage') || '{}');
  if (auth.state?.token) {
    config.headers.Authorization = `Bearer ${auth.state.token}`;
  }
  return config;
});

api.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) window.location.href = '/login';
    return Promise.reject(error);
  }
);
```

---

## 🧹 Clean Code & Prohibitions
- ❌ **No `any`**: TypeScript strict mode is mandatory.
- ❌ **No Direct DOM**: Use React refs or state.
- ❌ **No Inline Styles**: Use Tailwind classes for all styling.
- ❌ **No Massive Components**: Break down if logic/JSX exceeds 150 lines.
- ❌ **No Direct LocalStorage**: Use Zustand `persist` for application state.
- ❌ **No Prop Drilling**: Use Zustand for cross-component state.

---

## 📋 Quick Reference Checklist
- ✅ **React 19+** functional components and hooks.
- ✅ **TypeScript Strict Mode** enabled.
- ✅ **Vite** for all build tooling.
- ✅ **Tailwind CSS** for all styling.
- ✅ **Framer Motion** for animations.
- ✅ **Zustand** for state management with persistence.
- ✅ **Centralized API layer** in `/src/api/`.
- ✅ **Type-safe interfaces** for all data structures.
- ✅ **CI/CD pipeline** (lint, type-check, test, build).
- ✅ **Feature-based structure** for multi-module apps.
