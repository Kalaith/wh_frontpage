import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './utils/AuthContext';
import { AppHeader } from './components/layout/AppHeader';
import HomePage from './pages/HomePage';
import ProjectsPage from './pages/ProjectsPage';
import TrackerDashboard from './pages/TrackerDashboard';
import FeatureRequestsPage from './pages/FeatureRequestsPage';
import ProjectSuggestionsPage from './pages/ProjectSuggestionsPage';
import { FeatureRequestDashboard } from './pages/FeatureRequestDashboard';
import { UserProfile } from './pages/UserProfile';
import './styles/global.css'; // Ensure global styles are imported

const App: React.FC = () => {
  // basename is / because in this case we are using the htaccess to redirect / to /frontpage
  const basename = '/';

  return (
    <BrowserRouter basename={basename}>
      <AuthProvider>
        <div className="min-h-screen bg-gray-50">
          <AppHeader />
          <main>
            <Routes>
              <Route path="/" element={<HomePage />} />
              <Route path="/projects" element={<ProjectsPage />} />
              <Route path="/tracker" element={<TrackerDashboard />} />
              <Route path="/tracker/requests" element={<FeatureRequestsPage />} />
              <Route path="/tracker/suggestions" element={<ProjectSuggestionsPage />} />
              <Route path="/features" element={<FeatureRequestDashboard />} />
              <Route path="/profile" element={<UserProfile />} />
            </Routes>
          </main>
        </div>
      </AuthProvider>
    </BrowserRouter>
  );
};

export default App;
