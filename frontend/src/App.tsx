import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AppHeader } from './components/layout/AppHeader';
import HomePage from './pages/HomePage';
import ProjectsPage from './pages/ProjectsPage';
import TrackerDashboard from './pages/TrackerDashboard';
import FeatureRequestsPage from './pages/FeatureRequestsPage';
import ProjectSuggestionsPage from './pages/ProjectSuggestionsPage';
import { FeatureRequestDashboard } from './pages/FeatureRequestDashboard';
import { UserProfile } from './pages/UserProfile';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import AboutPage from './pages/AboutPage';
import './styles/global.css';

const App: React.FC = () => {
  const basename = '/';

  return (
    <BrowserRouter basename={basename}>
      <div className="min-h-screen bg-gray-50">
        <AppHeader />
        <main>
          <Routes>
            <Route path="/" element={<HomePage />} />
            <Route path="/projects" element={<ProjectsPage />} />
            <Route path="/about" element={<AboutPage />} />
            <Route path="/tracker" element={<TrackerDashboard />} />
            <Route path="/tracker/requests" element={<FeatureRequestsPage />} />
            <Route path="/tracker/suggestions" element={<ProjectSuggestionsPage />} />
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
