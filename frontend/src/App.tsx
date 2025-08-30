import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { useAuth0 } from '@auth0/auth0-react';
import { AuthProvider } from './utils/AuthContext';
import { AppHeader } from './components/layout/AppHeader';
import { setAuth0Client } from './utils/authToken';
import './utils/debugAuth'; // Load debug utilities
import HomePage from './pages/HomePage';
import ProjectsPage from './pages/ProjectsPage';
import TrackerDashboard from './pages/TrackerDashboard';
import FeatureRequestsPage from './pages/FeatureRequestsPage';
import ProjectSuggestionsPage from './pages/ProjectSuggestionsPage';
import { FeatureRequestDashboard } from './pages/FeatureRequestDashboard';
import { UserProfile } from './pages/UserProfile';
import './styles/global.css'; // Ensure global styles are imported

const App: React.FC = () => {
  const auth0 = useAuth0();
  
  // Initialize Auth0 client for token retrieval
  React.useEffect(() => {
    setAuth0Client(auth0);
  }, [auth0]);

  // basename is / because in this case we are using the htaccess to redirect / to /frontpage
  const basename = '/';

  // Show loading while Auth0 initializes
  if (auth0.isLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-lg text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

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
