import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AppHeader } from './components/layout/AppHeader';
import { ToastContainer } from './components/ui/Toast';
import { useToastStore } from './stores/toastStore';
import HomePage from './pages/HomePage';
import ProjectsPage from './pages/ProjectsPage';
import TrackerDashboard from './pages/TrackerDashboard';
import FeatureRequestsPage from './pages/FeatureRequestsPage';
import ProjectSuggestionsPage from './pages/ProjectSuggestionsPage';
import { FeatureRequestDashboard } from './pages/FeatureRequestDashboard';
import { UserProfile } from './pages/UserProfile';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import QuestBoardPage from './pages/QuestBoardPage';
import LeaderboardPage from './pages/LeaderboardPage';
import AdventurerProfilePage from './pages/AdventurerProfilePage';
import BossBattlePage from './pages/BossBattlePage';
import LootCratesPage from './pages/LootCratesPage';
import HallOfHeroesPage from './pages/HallOfHeroesPage';
import QuestChainsPage from './pages/QuestChainsPage';
import PortfolioPage from './pages/PortfolioPage';
import AboutPage from './pages/AboutPage';
import './styles/global.css';

import IdeasPage from './pages/IdeasPage';

const App: React.FC = () => {
  const basename = '/';
  const { toasts, removeToast } = useToastStore();

  return (
    <BrowserRouter basename={basename}>
      <div className="min-h-screen bg-gray-50">
        <ToastContainer toasts={toasts} onDismiss={removeToast} />
        <AppHeader />
        <main>
          <Routes>
            <Route path="/" element={<HomePage />} />
            <Route path="/projects" element={<ProjectsPage />} />
            <Route path="/quests" element={<QuestBoardPage />} />
            <Route path="/leaderboard" element={<LeaderboardPage />} />
            <Route path="/adventurers/:username" element={<AdventurerProfilePage />} />
            <Route path="/bosses" element={<BossBattlePage />} />
            <Route path="/loot" element={<LootCratesPage />} />
            <Route path="/hall-of-heroes" element={<HallOfHeroesPage />} />
            <Route path="/quest-chains" element={<QuestChainsPage />} />
            <Route path="/portfolio/:username" element={<PortfolioPage />} />
            <Route path="/about" element={<AboutPage />} />
            <Route path="/tracker" element={<TrackerDashboard />} />
            <Route path="/tracker/requests" element={<FeatureRequestsPage />} />
            <Route path="/tracker/suggestions" element={<ProjectSuggestionsPage />} />
            <Route path="/ideas" element={<IdeasPage />} />
            <Route path="/features" element={<FeatureRequestDashboard />} />
            <Route path="/profile" element={<UserProfile />} />
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
          </Routes>
        </main>
      </div>
    </BrowserRouter>
  );
};

export default App;

